<?php

namespace App\Http\Controllers;

use App\ERP\Accounting\Models\Account;
use App\ERP\Accounting\Models\JournalLine;
use App\ERP\Accounting\Services\CoaSettingService;
use App\ERP\Accounting\Models\JournalEntry;
use App\ERP\Core\Models\Company;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ERPAccountingUtilityController extends Controller
{
    private const POS_SALE_MODULES = ['pos_sale', 'pos_sale_reopen'];

    public function index(Request $request): Response
    {
        $companies = Company::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'legal_name', 'is_active']);

        $query = JournalEntry::query()
            ->with('company:id,name')
            ->withSum('lines as debit_total', 'debit')
            ->withSum('lines as credit_total', 'credit')
            ->latest('entry_date')
            ->latest('id');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('entry_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->where('entry_date', '<=', $request->string('date_to')->toString());
        }

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(function ($inner) use ($term): void {
                $inner->where('entry_no', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhere('source_module', 'like', '%'.$term.'%');
            });
        }

        $entries = $query
            ->paginate($this->resolvedPerPage($request))
            ->withQueryString()
            ->through(fn (JournalEntry $entry) => [
                'id' => $entry->id,
                'entry_no' => $entry->entry_no,
                'entry_date' => $entry->entry_date?->format('Y-m-d'),
                'description' => $entry->description,
                'source_module' => $entry->source_module,
                'source_reference' => $entry->source_reference,
                'company_id' => $entry->company_id,
                'company_name' => $entry->company?->name ?? 'Belum ditentukan',
                'debit_total' => (float) ($entry->debit_total ?? 0),
                'credit_total' => (float) ($entry->credit_total ?? 0),
            ]);

        $companySummaries = JournalEntry::query()
            ->leftJoin('companies', 'companies.id', '=', 'journal_entries.company_id')
            ->selectRaw('COALESCE(companies.name, ?) as company_name', ['Belum ditentukan'])
            ->selectRaw('journal_entries.company_id')
            ->selectRaw('COUNT(*) as entry_count')
            ->groupBy('journal_entries.company_id', 'companies.name')
            ->orderBy('companies.name')
            ->get()
            ->map(fn ($row) => [
                'company_id' => $row->company_id,
                'company_name' => $row->company_name,
                'entry_count' => (int) $row->entry_count,
            ]);

        return Inertia::render('ERP/Accounting/Utilities', [
            'companies' => $companies,
            'entries' => $entries,
            'companySummaries' => $companySummaries,
            'filters' => $this->filtersWithPerPage($request, ['company_id', 'date_from', 'date_to', 'q']),
            'posChannelCorrection' => $this->posChannelCorrectionSummary($request),
        ]);
    }

    public function moveJournalEntries(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_company_id' => ['required', 'integer', 'exists:companies,id'],
            'journal_entry_ids' => ['required', 'array', 'min:1'],
            'journal_entry_ids.*' => ['integer', 'exists:journal_entries,id'],
        ]);

        $targetCompanyId = (int) $validated['target_company_id'];
        $journalEntryIds = collect($validated['journal_entry_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $movedCount = DB::transaction(function () use ($journalEntryIds, $targetCompanyId): int {
            return JournalEntry::query()
                ->whereIn('id', $journalEntryIds)
                ->where(function ($query) use ($targetCompanyId): void {
                    $query->where('company_id', '!=', $targetCompanyId)
                        ->orWhereNull('company_id');
                })
                ->update(['company_id' => $targetCompanyId]);
        });

        $target = Company::query()->find($targetCompanyId);

        return back()->with('flash', [
            'type' => 'success',
            'message' => $movedCount.' transaksi accounting dipindahkan ke '.($target?->name ?? 'usaha tujuan').'.',
        ]);
    }

    public function correctPosChannelPayable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'journal_entry_ids' => ['required', 'array', 'min:1'],
            'journal_entry_ids.*' => ['integer', 'exists:journal_entries,id'],
        ]);

        [$expenseAccount, $payableAccount] = $this->posChannelCorrectionAccounts();

        $journalEntryIds = collect($validated['journal_entry_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $correctedCount = DB::transaction(function () use ($journalEntryIds, $expenseAccount, $payableAccount): int {
            $lines = JournalLine::query()
                ->with(['journalEntry.lines'])
                ->whereIn('journal_entry_id', $journalEntryIds)
                ->where('account_id', $expenseAccount->id)
                ->where('credit', '>', 0)
                ->whereHas('journalEntry', fn ($entry) => $entry->whereIn('source_module', self::POS_SALE_MODULES))
                ->get();

            $count = 0;
            foreach ($lines as $line) {
                $matchingDebit = $line->journalEntry?->lines
                    ->contains(fn (JournalLine $candidate): bool => (int) $candidate->account_id === (int) $expenseAccount->id
                        && (float) $candidate->debit > 0
                        && abs((float) $candidate->debit - (float) $line->credit) < 0.01);

                if (! $matchingDebit) {
                    continue;
                }

                $line->update(['account_id' => $payableAccount->id]);
                $count++;
            }

            return $count;
        });

        return back()->with('flash', [
            'type' => $correctedCount > 0 ? 'success' : 'warning',
            'message' => $correctedCount > 0
                ? $correctedCount.' baris kredit biaya admin channel dikoreksi ke '.$payableAccount->code.' - '.$payableAccount->name.'.'
                : 'Tidak ada baris jurnal yang cocok untuk dikoreksi.',
        ]);
    }

    private function posChannelCorrectionSummary(Request $request): array
    {
        try {
            [$expenseAccount, $payableAccount] = $this->posChannelCorrectionAccounts();
        } catch (ValidationException $exception) {
            return [
                'can_correct' => false,
                'message' => collect($exception->errors())->flatten()->first(),
                'candidate_count' => 0,
            ];
        }

        $candidateCount = JournalLine::query()
            ->where('account_id', $expenseAccount->id)
            ->where('credit', '>', 0)
            ->whereHas('journalEntry', function ($entry) use ($request): void {
                $entry->whereIn('source_module', self::POS_SALE_MODULES);

                if ($request->filled('company_id')) {
                    $entry->where('company_id', $request->integer('company_id'));
                }

                if ($request->filled('date_from')) {
                    $entry->where('entry_date', '>=', $request->string('date_from')->toString());
                }

                if ($request->filled('date_to')) {
                    $entry->where('entry_date', '<=', $request->string('date_to')->toString());
                }

                if ($request->filled('q')) {
                    $term = $request->string('q')->toString();
                    $entry->where(function ($inner) use ($term): void {
                        $inner->where('entry_no', 'like', '%'.$term.'%')
                            ->orWhere('description', 'like', '%'.$term.'%')
                            ->orWhere('source_module', 'like', '%'.$term.'%');
                    });
                }
            })
            ->count();

        return [
            'can_correct' => true,
            'expense_account' => $this->accountLabel($expenseAccount),
            'payable_account' => $this->accountLabel($payableAccount),
            'candidate_count' => $candidateCount,
        ];
    }

    private function posChannelCorrectionAccounts(): array
    {
        $coa = app(CoaSettingService::class);
        try {
            $expenseAccount = $coa->resolveAccountByKey('pos_sale_sales_channel_admin_expense', '5016');
            $payableAccount = $coa->resolveAccountByKey('pos_sale_sales_channel_admin_payable', '2090');
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages([
                'account' => 'Akun default POS admin channel belum tersedia. Lengkapi Pengaturan COA terlebih dahulu.',
            ]);
        }

        if ((int) $expenseAccount->id === (int) $payableAccount->id) {
            throw ValidationException::withMessages([
                'account' => 'Akun beban admin channel dan hutang estimasi channel masih sama. Ubah Pengaturan COA terlebih dahulu.',
            ]);
        }

        return [$expenseAccount, $payableAccount];
    }

    private function accountLabel(Account $account): string
    {
        return $account->code.' - '.$account->name;
    }
}
