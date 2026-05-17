<?php

namespace App\ERP\Accounting\Services;

use App\ERP\Accounting\Models\Account;
use App\ERP\Accounting\Models\JournalLine;
use App\Models\CashIn;
use App\Models\CashOut;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CashAccountReassignmentService
{
    /**
     * @return list<array{account_id: int, label: string, cash_in_count: int, cash_out_count: int}>
     */
    public function countsBySourceAccount(): array
    {
        $accounts = Account::cashBankOptions()->keyBy('id');
        if ($accounts->isEmpty()) {
            return [];
        }

        $cashInCounts = CashIn::query()
            ->whereIn('cash_account_id', $accounts->keys())
            ->selectRaw('cash_account_id, COUNT(*) as c')
            ->groupBy('cash_account_id')
            ->pluck('c', 'cash_account_id');

        $cashOutCounts = CashOut::query()
            ->whereIn('cash_account_id', $accounts->keys())
            ->selectRaw('cash_account_id, COUNT(*) as c')
            ->groupBy('cash_account_id')
            ->pluck('c', 'cash_account_id');

        return $accounts
            ->map(fn (Account $account) => [
                'account_id' => $account->id,
                'label' => $account->displayLabel(),
                'cash_in_count' => (int) ($cashInCounts[$account->id] ?? 0),
                'cash_out_count' => (int) ($cashOutCounts[$account->id] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     cash_in_count: int,
     *     cash_out_count: int,
     *     journal_lines_count: int,
     *     samples: list<array<string, mixed>>
     * }
     */
    public function preview(int $fromAccountId, ?string $dateFrom = null, ?string $dateTo = null, int $sampleLimit = 20): array
    {
        $cashInRows = $this->cashInQuery($fromAccountId, $dateFrom, $dateTo)->get();
        $cashOutRows = $this->cashOutQuery($fromAccountId, $dateFrom, $dateTo)->get();

        $journalLineCount = $this->countJournalLinesToUpdate($cashInRows, $cashOutRows, $fromAccountId);

        $samples = $cashInRows
            ->map(fn (CashIn $row) => $this->sampleRow('cash_in', $row))
            ->concat($cashOutRows->map(fn (CashOut $row) => $this->sampleRow('cash_out', $row)))
            ->take($sampleLimit)
            ->values()
            ->all();

        return [
            'cash_in_count' => $cashInRows->count(),
            'cash_out_count' => $cashOutRows->count(),
            'journal_lines_count' => $journalLineCount,
            'samples' => $samples,
        ];
    }

    /**
     * @return array{cash_in_updated: int, cash_out_updated: int, journal_lines_updated: int}
     */
    public function apply(
        int $fromAccountId,
        int $toAccountId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        return DB::transaction(function () use ($fromAccountId, $toAccountId, $dateFrom, $dateTo): array {
            $cashInRows = $this->cashInQuery($fromAccountId, $dateFrom, $dateTo)->get();
            $cashOutRows = $this->cashOutQuery($fromAccountId, $dateFrom, $dateTo)->get();

            $cashInUpdated = 0;
            foreach ($cashInRows as $row) {
                $row->update(['cash_account_id' => $toAccountId]);
                $cashInUpdated++;
            }

            $cashOutUpdated = 0;
            foreach ($cashOutRows as $row) {
                $row->update(['cash_account_id' => $toAccountId]);
                $cashOutUpdated++;
            }

            $journalLinesUpdated = $this->updateJournalLines($cashInRows, $cashOutRows, $fromAccountId, $toAccountId);

            return [
                'cash_in_updated' => $cashInUpdated,
                'cash_out_updated' => $cashOutUpdated,
                'journal_lines_updated' => $journalLinesUpdated,
            ];
        });
    }

    private function cashInQuery(int $fromAccountId, ?string $dateFrom, ?string $dateTo)
    {
        $query = CashIn::query()
            ->where('cash_account_id', $fromAccountId)
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        return $query;
    }

    private function cashOutQuery(int $fromAccountId, ?string $dateFrom, ?string $dateTo)
    {
        $query = CashOut::query()
            ->where('cash_account_id', $fromAccountId)
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        return $query;
    }

    private function countJournalLinesToUpdate(Collection $cashInRows, Collection $cashOutRows, int $fromAccountId): int
    {
        $entryIds = $cashInRows->pluck('journal_entry_id')
            ->merge($cashOutRows->pluck('journal_entry_id'))
            ->filter()
            ->unique()
            ->values();

        if ($entryIds->isEmpty()) {
            return 0;
        }

        return (int) JournalLine::query()
            ->whereIn('journal_entry_id', $entryIds)
            ->where('account_id', $fromAccountId)
            ->where(function ($query): void {
                $query->where('debit', '>', 0)
                    ->orWhere('credit', '>', 0);
            })
            ->count();
    }

    private function updateJournalLines(
        Collection $cashInRows,
        Collection $cashOutRows,
        int $fromAccountId,
        int $toAccountId,
    ): int {
        $updated = 0;

        foreach ($cashInRows as $row) {
            if (! $row->journal_entry_id) {
                continue;
            }

            $updated += JournalLine::query()
                ->where('journal_entry_id', $row->journal_entry_id)
                ->where('account_id', $fromAccountId)
                ->where('debit', '>', 0)
                ->update(['account_id' => $toAccountId]);
        }

        foreach ($cashOutRows as $row) {
            if (! $row->journal_entry_id) {
                continue;
            }

            $updated += JournalLine::query()
                ->where('journal_entry_id', $row->journal_entry_id)
                ->where('account_id', $fromAccountId)
                ->where('credit', '>', 0)
                ->update(['account_id' => $toAccountId]);
        }

        return $updated;
    }

    private function sampleRow(string $domain, CashIn|CashOut $row): array
    {
        return [
            'domain' => $domain,
            'id' => $row->id,
            'date' => $row->date?->format('Y-m-d'),
            'amount' => (float) $row->amount,
            'category' => $row->category,
            'note' => $row->note,
            'has_journal' => (bool) $row->journal_entry_id,
        ];
    }
}
