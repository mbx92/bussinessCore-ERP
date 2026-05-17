<?php

namespace App\ERP\Accounting\Support;

use App\ERP\Accounting\Models\Account;
use App\ERP\Accounting\Models\JournalLine;
use Illuminate\Support\Collection;

class CashAccountLabelResolver
{
    /**
     * @param  Collection<int, JournalLine>|null  $debitLinesByJournal
     */
    public static function label(?Account $cashAccount, ?int $journalEntryId, ?Collection $debitLinesByJournal = null): string
    {
        if ($cashAccount) {
            return $cashAccount->displayLabel();
        }

        if (! $journalEntryId) {
            return '-';
        }

        $debitLine = $debitLinesByJournal?->get($journalEntryId);

        if (! $debitLine) {
            $debitLine = JournalLine::query()
                ->with('account:id,code,name')
                ->where('journal_entry_id', $journalEntryId)
                ->where('debit', '>', 0)
                ->first();
        }

        if ($debitLine?->account) {
            return $debitLine->account->displayLabel();
        }

        return '-';
    }
}
