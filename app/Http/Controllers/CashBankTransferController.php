<?php

namespace App\Http\Controllers;

use App\ERP\Accounting\Models\Account;
use App\ERP\Accounting\Services\GlPostingService;
use App\ERP\Core\Services\ErpCompanyResolver;
use App\Models\CashBankTransfer;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CashBankTransferController extends Controller
{
    public function __construct(private readonly GlPostingService $glPostingService) {}

    public function index(Request $request): Response
    {
        $companyId = ErpCompanyResolver::resolveForReporting($request);

        $query = CashBankTransfer::query()
            ->with([
                'fromAccount:id,code,name',
                'toAccount:id,code,name',
                'project:id,name',
                'creator:id,name',
                'journalEntry:id,entry_no,company_id',
                'journalEntry.company:id,name',
            ])
            ->when($companyId, fn ($q) => $q->whereHas(
                'journalEntry',
                fn ($jq) => $jq->where('company_id', $companyId)
            ))
            ->latest('transfer_date')
            ->latest('id');

        if ($request->filled('date_from')) {
            $query->where('transfer_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->where('transfer_date', '<=', $request->string('date_to')->toString());
        }

        if ($request->filled('from_account_id')) {
            $query->where('from_account_id', $request->integer('from_account_id'));
        }

        if ($request->filled('to_account_id')) {
            $query->where('to_account_id', $request->integer('to_account_id'));
        }

        if ($request->filled('project_id') && Str::isUuid($request->string('project_id')->toString())) {
            $query->where('project_id', $request->string('project_id')->toString());
        }

        $total = (float) (clone $query)->sum('amount');

        $transfers = $query
            ->paginate($this->resolvedPerPage($request))
            ->withQueryString()
            ->through(fn (CashBankTransfer $row) => [
                'id' => $row->id,
                'transfer_date' => $row->transfer_date?->format('Y-m-d'),
                'amount' => (float) $row->amount,
                'note' => $row->note,
                'from_account_id' => $row->from_account_id,
                'from_account_label' => $row->fromAccount?->displayLabel(),
                'to_account_id' => $row->to_account_id,
                'to_account_label' => $row->toAccount?->displayLabel(),
                'project_id' => $row->project_id,
                'project_name' => $row->project?->name,
                'journal_entry_no' => $row->journalEntry?->entry_no,
                'company_name' => $row->journalEntry?->company?->name ?? 'Belum ditentukan',
                'creator_name' => $row->creator?->name,
            ]);

        return Inertia::render('ERP/Accounting/CashBankTransfer', [
            'transfers' => $transfers,
            'total' => $total,
            'cashAccounts' => Account::cashBankOptions(),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $this->filtersWithPerPage($request, [
                'company_id',
                'date_from',
                'date_to',
                'from_account_id',
                'to_account_id',
                'project_id',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_account_id' => Account::cashBankIdValidationRules(),
            'to_account_id' => Account::cashBankIdValidationRules(),
            'amount' => ['required', 'numeric', 'min:1'],
            'transfer_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
        ]);

        $fromAccountId = (int) $validated['from_account_id'];
        $toAccountId = (int) $validated['to_account_id'];

        if ($fromAccountId === $toAccountId) {
            throw ValidationException::withMessages([
                'to_account_id' => 'Akun tujuan harus berbeda dari akun sumber.',
            ]);
        }

        $fromAccount = Account::query()->findOrFail($fromAccountId);
        $toAccount = Account::query()->findOrFail($toAccountId);
        $amount = (float) $validated['amount'];
        $note = trim((string) ($validated['note'] ?? ''));
        $project = ! empty($validated['project_id'])
            ? Project::query()->find($validated['project_id'])
            : null;

        DB::transaction(function () use ($request, $validated, $fromAccount, $toAccount, $amount, $note, $project): void {
            $transfer = CashBankTransfer::query()->create([
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'amount' => $amount,
                'transfer_date' => $validated['transfer_date'],
                'note' => $note !== '' ? $note : null,
                'project_id' => $project?->id,
                'created_by' => Auth::id(),
            ]);

            $description = $note !== ''
                ? $note
                : "Mutasi kas/bank {$fromAccount->code} → {$toAccount->code}";

            if ($project) {
                $description .= " (referensi project: {$project->name})";
            }

            $entry = $this->glPostingService->post(
                ErpCompanyResolver::resolveForGlPosting($request),
                sourceModule: 'cash_bank_transfer',
                sourceReference: (string) $transfer->id,
                description: $description,
                entryDate: $validated['transfer_date'],
                lines: [
                    ['account_id' => $toAccount->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $fromAccount->id, 'debit' => 0, 'credit' => $amount],
                ],
            );

            $transfer->update(['journal_entry_id' => $entry->id]);
        });

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Mutasi kas/bank berhasil dicatat.',
        ]);
    }
}
