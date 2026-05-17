<?php

namespace App\Console\Commands;

use App\ERP\Accounting\Models\JournalLine;
use App\Models\CashIn;
use App\Models\CashOut;
use Illuminate\Console\Command;
class BackfillCashAccountIdsCommand extends Command
{
    protected $signature = 'accounting:backfill-cash-account-ids
                            {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Isi cash_account_id kosong pada cash_in/cash_out dari baris debit jurnal terkait';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Mode dry-run: tidak ada perubahan disimpan.');
        }

        $cashInUpdated = $this->backfillCashIn($dryRun);
        $cashOutUpdated = $this->backfillCashOut($dryRun);

        $this->info("Selesai. cash_in: {$cashInUpdated}, cash_out: {$cashOutUpdated} baris diperbarui.");

        if ($dryRun && ($cashInUpdated > 0 || $cashOutUpdated > 0)) {
            $this->comment('Jalankan tanpa --dry-run untuk menerapkan.');
        }

        return self::SUCCESS;
    }

    private function backfillCashIn(bool $dryRun): int
    {
        $rows = CashIn::query()
            ->whereNull('cash_account_id')
            ->whereNotNull('journal_entry_id')
            ->get(['id', 'journal_entry_id']);

        if ($rows->isEmpty()) {
            $this->line('cash_in: tidak ada baris tanpa cash_account_id.');

            return 0;
        }

        $debitAccounts = $this->debitAccountIdsByJournal($rows->pluck('journal_entry_id'));
        $updated = 0;

        foreach ($rows as $row) {
            $accountId = $debitAccounts->get($row->journal_entry_id);
            if (! $accountId) {
                $this->warn("cash_in #{$row->id}: jurnal {$row->journal_entry_id} tanpa baris debit kas.");

                continue;
            }

            if (! $dryRun) {
                CashIn::query()->whereKey($row->id)->update(['cash_account_id' => $accountId]);
            }

            $updated++;
        }

        return $updated;
    }

    private function backfillCashOut(bool $dryRun): int
    {
        $rows = CashOut::query()
            ->whereNull('cash_account_id')
            ->whereNotNull('journal_entry_id')
            ->get(['id', 'journal_entry_id']);

        if ($rows->isEmpty()) {
            $this->line('cash_out: tidak ada baris tanpa cash_account_id.');

            return 0;
        }

        $creditAccounts = JournalLine::query()
            ->whereIn('journal_entry_id', $rows->pluck('journal_entry_id')->unique())
            ->where('credit', '>', 0)
            ->get(['journal_entry_id', 'account_id'])
            ->keyBy('journal_entry_id');

        $updated = 0;

        foreach ($rows as $row) {
            $line = $creditAccounts->get($row->journal_entry_id);
            if (! $line?->account_id) {
                $this->warn("cash_out #{$row->id}: jurnal {$row->journal_entry_id} tanpa baris kredit kas.");

                continue;
            }

            if (! $dryRun) {
                CashOut::query()->whereKey($row->id)->update(['cash_account_id' => $line->account_id]);
            }

            $updated++;
        }

        return $updated;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int|string|null>  $journalEntryIds
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function debitAccountIdsByJournal($journalEntryIds)
    {
        return JournalLine::query()
            ->whereIn('journal_entry_id', $journalEntryIds->unique()->filter())
            ->where('debit', '>', 0)
            ->get(['journal_entry_id', 'account_id'])
            ->keyBy('journal_entry_id')
            ->map(fn (JournalLine $line) => (int) $line->account_id);
    }
}
