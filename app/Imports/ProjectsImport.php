<?php

namespace App\Imports;

use App\ERP\Inventory\Models\Warehouse;
use App\Models\MasterProduct;
use App\Models\MasterProductWarehouseStock;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\ProjectPayment;
use App\Models\ProjectType;
use App\Models\TeamDistribution;
use App\Models\TeamRole;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProjectsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    /** @var list<array{row: int, message: string}> */
    public array $errors = [];

    /** @var list<string> emails auto-created during import */
    public array $autoCreatedUsers = [];

    /** @var array<string, Project> import_key => Project (projects created/updated in this session) */
    private array $sessionProjects = [];

    public function collection(Collection $rows): void
    {
        $line = 1;

        foreach ($rows as $row) {
            $line++;
            $data = $this->normalizeRow($row);
            if ($this->isEmptyRow($data)) {
                continue;
            }

            $name = isset($data['name']) ? trim((string) $data['name']) : '';

            $importKey = isset($data['import_key']) ? trim((string) $data['import_key']) : '';
            if ($importKey === '') {
                $importKey = null;
            }

            // Multi-row: if this import_key was already processed, just add item/team
            if ($importKey && isset($this->sessionProjects[$importKey])) {
                $project = $this->sessionProjects[$importKey];
                $label = $name !== '' ? $name : $importKey;
                $this->safeMaterialUpsert($project, $data, $line, "{$label} (baris tambahan)");
                $this->safeTeamUpsert($project, $data, $line, "{$label} (baris tambahan)");

                continue;
            }

            if ($name === '') {
                $this->errors[] = ['row' => $line, 'message' => 'Kolom name wajib diisi.'];

                continue;
            }

            $clientName = isset($data['client_name']) ? trim((string) $data['client_name']) : '';
            if ($clientName === '') {
                $this->errors[] = ['row' => $line, 'message' => "Project \"{$name}\": client_name wajib diisi."];

                continue;
            }

            $totalValue = $this->toDecimal($data['total_value'] ?? 0);
            if ((float) $totalValue < 0) {
                $this->errors[] = ['row' => $line, 'message' => "Project \"{$name}\": total_value tidak boleh negatif."];

                continue;
            }

            $status = strtolower(trim((string) ($data['status'] ?? 'negosiasi')));
            if (! in_array($status, ['negosiasi', 'berjalan', 'selesai', 'dibatalkan'], true)) {
                $this->errors[] = ['row' => $line, 'message' => "Project \"{$name}\": status harus negosiasi, berjalan, selesai, atau dibatalkan."];

                continue;
            }

            $projectType = strtolower(trim((string) ($data['project_type'] ?? ProjectType::defaultKey())));
            $allowedProjectTypes = ProjectType::query()->pluck('key')->all();
            if (! in_array($projectType, $allowedProjectTypes, true)) {
                $this->errors[] = ['row' => $line, 'message' => "Project \"{$name}\": project_type tidak ditemukan di master tipe project."];

                continue;
            }

            $clientContact = isset($data['client_contact']) ? trim((string) $data['client_contact']) : '';
            if ($clientContact === '') {
                $clientContact = null;
            }

            $description = isset($data['description']) ? trim((string) $data['description']) : null;
            if ($description === '') {
                $description = null;
            }

            $invoiceNumber = isset($data['invoice_number']) ? trim((string) $data['invoice_number']) : '';
            if ($invoiceNumber === '') {
                $invoiceNumber = null;
            }

            $existing = $importKey
                ? Project::query()->where('import_key', $importKey)->first()
                : null;

            $startedAt = $this->parseDate($data['started_at'] ?? null);
            $finishedAt = $this->parseDate($data['finished_at'] ?? null);

            if ($startedAt && $finishedAt && $finishedAt < $startedAt) {
                $this->errors[] = ['row' => $line, 'message' => "Project \"{$name}\": finished_at harus sama atau setelah started_at."];

                continue;
            }

            $terms = $this->parsePaymentTerms($data);
            if ($terms === null) {
                $this->errors[] = ['row' => $line, 'message' => "Project \"{$name}\": term_percentages tidak valid (pisahkan dengan koma; total harus 100%)."];

                continue;
            }

            if ($invoiceNumber) {
                $dupInvoice = Project::query()
                    ->where('invoice_number', $invoiceNumber)
                    ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                    ->exists();
                if ($dupInvoice) {
                    $this->errors[] = ['row' => $line, 'message' => "Project \"{$name}\": invoice_number sudah dipakai project lain."];

                    continue;
                }
            }

            if ($existing && $existing->payments()->whereNotNull('paid_at')->exists()) {
                $this->errors[] = ['row' => $line, 'message' => "import_key \"{$importKey}\": ada termin yang sudah lunas; baris dilewati (ubah manual di UI)."];

                continue;
            }

            try {
                DB::transaction(function () use ($existing, $name, $clientName, $clientContact, $projectType, $totalValue, $status, $invoiceNumber, $startedAt, $finishedAt, $description, $importKey, $terms, $data): void {
                    $payload = [
                        'name' => $name,
                        'client_name' => $clientName,
                        'client_contact' => $clientContact,
                        'project_type' => $projectType,
                        'total_value' => $totalValue,
                        'status' => $status,
                        'invoice_number' => $invoiceNumber,
                        'started_at' => $startedAt,
                        'finished_at' => $finishedAt,
                        'description' => $description,
                        'import_key' => $importKey,
                    ];

                    if ($existing) {
                        $existing->update($payload);
                        $project = $existing;
                        $project->payments()->delete();
                    } else {
                        $project = Project::query()->create($payload);
                    }

                    if ($importKey) {
                        $this->sessionProjects[$importKey] = $project;
                    }

                    $this->createPaymentRowsForProject($project, $terms);
                    $this->safeMaterialUpsert($project, $data, $line, $name);
                    $this->safeTeamUpsert($project, $data, $line, $name);
                });
            } catch (\Throwable $e) {
                $this->errors[] = ['row' => $line, 'message' => "Project \"{$name}\": ".$e->getMessage()];

                continue;
            }

            $this->imported++;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{percentage: float, note: string|null}>|null
     */
    private function parsePaymentTerms(array $data): ?array
    {
        $raw = $data['term_percentages'] ?? $data['payment_terms'] ?? '';
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [['percentage' => 100.0, 'note' => 'Impor — pelunasan tunggal']];
        }

        $parts = preg_split('/\s*[,|]\s*/', $raw) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
        if ($parts === []) {
            return null;
        }

        $notesRaw = isset($data['term_notes']) ? trim((string) $data['term_notes']) : '';
        $noteParts = $notesRaw !== '' ? preg_split('/\s*\|\s*/', $notesRaw) : [];
        $noteParts = array_map(fn ($n) => trim((string) $n), $noteParts);

        $terms = [];
        $sum = 0.0;
        foreach ($parts as $i => $p) {
            $pct = (float) str_replace(',', '.', $p);
            if ($pct <= 0 || $pct > 100) {
                return null;
            }
            $sum += $pct;
            $note = $noteParts[$i] ?? null;
            if ($note === '') {
                $note = null;
            }
            $terms[] = ['percentage' => $pct, 'note' => $note];
        }

        if (abs($sum - 100) > 0.02) {
            return null;
        }

        return $terms;
    }

    private function createPaymentRowsForProject(Project $project, array $payments): void
    {
        $totalValue = (float) $project->total_value;
        $n = count($payments);
        $assigned = 0.0;

        foreach ($payments as $i => $term) {
            $pct = (float) $term['percentage'];
            if ($i === $n - 1) {
                $amount = round($totalValue - $assigned, 2);
            } else {
                $amount = round($totalValue * ($pct / 100), 2);
                $assigned += $amount;
            }

            ProjectPayment::create([
                'project_id' => $project->id,
                'term_number' => $i + 1,
                'percentage' => $pct,
                'amount' => $amount,
                'note' => $term['note'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertProjectMaterialFromRow(Project $project, array $data): void
    {
        $sku = trim((string) ($data['item_sku'] ?? $data['material_sku'] ?? ''));
        $warehouseCode = trim((string) ($data['item_warehouse_code'] ?? $data['material_warehouse_code'] ?? ''));
        $plannedRaw = $data['item_planned_qty'] ?? $data['material_planned_qty'] ?? null;
        $notes = trim((string) ($data['item_notes'] ?? $data['material_notes'] ?? ''));
        $status = strtolower(trim((string) ($data['item_status'] ?? $data['material_status'] ?? 'reserved')));

        $reservedRaw = $data['item_reserved_qty'] ?? $data['material_reserved_qty'] ?? null;
        $issuedRaw = $data['item_issued_qty'] ?? $data['material_issued_qty'] ?? null;

        if ($sku === '') {
            return;
        }

        $product = MasterProduct::query()
            ->where('sku', $sku)
            ->first();
        if (! $product) {
            throw new \RuntimeException("item_sku \"{$sku}\" tidak ditemukan di master produk.");
        }

        $plannedQty = (float) $this->toDecimal($plannedRaw);
        if ($plannedQty <= 0) {
            throw new \RuntimeException("Item {$sku}: item_planned_qty harus lebih dari 0.");
        }

        $reservedQty = (float) $this->toDecimal($reservedRaw);
        $issuedQty = (float) $this->toDecimal($issuedRaw);
        $reservedQty = max($reservedQty, 0);
        $issuedQty = max($issuedQty, 0);
        $reservedQty = min($reservedQty, $plannedQty);
        $issuedQty = min($issuedQty, $reservedQty);

        if ($status === '') {
            $status = 'reserved';
        }

        $warehouse = $warehouseCode !== ''
            ? Warehouse::query()->where('code', $warehouseCode)->first()
            : Warehouse::query()->where('is_active', true)->orderBy('name')->first();

        if (! $warehouse) {
            throw new \RuntimeException(
                $warehouseCode !== ''
                    ? "Warehouse dengan kode \"{$warehouseCode}\" tidak ditemukan."
                    : 'Warehouse aktif tidak ditemukan untuk item project.'
            );
        }

        MasterProductWarehouseStock::query()->firstOrCreate(
            [
                'master_product_id' => (int) $product->id,
                'warehouse_id' => (int) $warehouse->id,
            ],
            ['qty' => 0, 'reserved_qty' => 0]
        );

        ProjectMaterial::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'master_product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'planned_qty' => number_format($plannedQty, 2, '.', ''),
                'reserved_qty' => number_format($reservedQty, 2, '.', ''),
                'issued_qty' => number_format($issuedQty, 2, '.', ''),
                'status' => substr($status, 0, 20),
                'notes' => $notes !== '' ? $notes : null,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertTeamMemberFromRow(Project $project, array $data): void
    {
        $email = trim((string) ($data['team_email'] ?? ''));
        $role = strtolower(trim((string) ($data['team_role'] ?? '')));
        $pct = $data['team_percentage'] ?? null;
        $bonus = $data['team_bonus'] ?? null;

        $hasTeamPayload = $email !== ''
            || $role !== ''
            || ($pct !== null && $pct !== '')
            || ($bonus !== null && $bonus !== '');

        if (! $hasTeamPayload) {
            return;
        }

        if ($email === '') {
            throw new \RuntimeException('team_email wajib diisi jika ingin import anggota tim.');
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $namePart = Str::before($email, '@');
            $user = User::query()->create([
                'name' => Str::title(str_replace(['.', '_', '-'], ' ', $namePart)),
                'email' => $email,
                'password' => bcrypt('password'),
                'role' => 'technician',
            ]);
            $this->autoCreatedUsers[] = $email;
        }

        if ($role === '') {
            $role = 'technician';
        }

        $teamRole = TeamRole::query()->where('name', $role)->first();
        if (! $teamRole) {
            TeamRole::query()->create(['name' => $role, 'is_active' => true]);
        }

        $percentage = (float) $this->toDecimal($pct);
        if ($percentage <= 0 || $percentage > 100) {
            $percentage = max(min($percentage, 100), 1);
        }

        $bonusAmount = (float) $this->toDecimal($bonus);
        $totalValue = (float) $project->total_value;
        $basePay = round($totalValue * ($percentage / 100), 2);
        $totalPay = $basePay + $bonusAmount;

        TeamDistribution::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $user->id,
            ],
            [
                'role_in_project' => $role,
                'percentage' => $percentage,
                'base_pay' => number_format($basePay, 2, '.', ''),
                'bonus' => number_format($bonusAmount, 2, '.', ''),
                'total_pay' => number_format($totalPay, 2, '.', ''),
            ]
        );
    }

    private function safeMaterialUpsert(Project $project, array $data, int $line, string $label): void
    {
        try {
            $this->upsertProjectMaterialFromRow($project, $data);
        } catch (\Throwable $e) {
            $this->errors[] = ['row' => $line, 'message' => "Project \"{$label}\": ".$e->getMessage()];
        }
    }

    private function safeTeamUpsert(Project $project, array $data, int $line, string $label): void
    {
        try {
            $this->upsertTeamMemberFromRow($project, $data);
        } catch (\Throwable $e) {
            $this->errors[] = ['row' => $line, 'message' => "Project \"{$label}\": ".$e->getMessage()];
        }
    }

    /**
     * @param  Collection<int, mixed>|array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(Collection|array $row): array
    {
        $arr = is_array($row) ? $row : $row->toArray();
        $out = [];
        foreach ($arr as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $k = Str::slug((string) $key, '_');
            $out[$k] = $value;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $v) {
            if ($v !== null && $v !== '') {
                return false;
            }
        }

        return true;
    }

    private function toDecimal(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '0.00';
        }
        if (is_numeric($v)) {
            return number_format((float) $v, 2, '.', '');
        }
        $s = str_replace([','], ['.'], preg_replace('/[^\d.,\-]/', '', (string) $v));

        return number_format((float) $s, 2, '.', '');
    }

    private function parseDate(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d');
        }
        if (is_numeric($v)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $v)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }
        try {
            return Carbon::parse($s)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
