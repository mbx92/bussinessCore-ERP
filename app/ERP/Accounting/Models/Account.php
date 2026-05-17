<?php

namespace App\ERP\Accounting\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class Account extends Model
{
    /** @var list<string> */
    public const CASH_BANK_SETTING_KEYS = [
        'pos_sale_cash_account',
        'project_invoice_cash_account',
    ];

    /**
     * Akun kas/bank untuk dropdown pembayaran — tidak mengunci prefix 100x.
     * Sumber (berurutan): Pengaturan COA → config ACCOUNTING_CASH_BANK_CODES → nama Kas/Bank.
     */
    public static function cashBankOptions(): Collection
    {
        $accountIds = static::cashBankAccountIdsFromSettings();
        $configCodes = config('accounting.cash_bank_account_codes', []);

        if ($configCodes !== []) {
            $fromConfig = static::query()
                ->where('is_active', true)
                ->where('type', 'asset')
                ->whereIn('code', $configCodes)
                ->pluck('id');

            $accountIds = $accountIds->merge($fromConfig)->unique()->values();
        }

        if ($accountIds->isNotEmpty()) {
            return static::query()
                ->whereIn('id', $accountIds)
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']);
        }

        return static::query()
            ->where('is_active', true)
            ->where('type', 'asset')
            ->where(function ($query): void {
                $query->where('name', 'like', '%Kas%')
                    ->orWhere('name', 'like', '%Bank%')
                    ->orWhere('name', 'like', '%Cash%')
                    ->orWhere('name', 'like', '%Giro%');
            })
            ->where(function ($query): void {
                $query->where('name', 'not like', '%Piutang%')
                    ->where('name', 'not like', '%Persediaan%')
                    ->where('name', 'not like', '%Peralatan%')
                    ->where('name', 'not like', '%Kendaraan%')
                    ->where('name', 'not like', '%Akumulasi%')
                    ->where('name', 'not like', '%Sewa Dibayar%')
                    ->where('name', 'not like', '%Asuransi Dibayar%');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function cashBankAccountIdsFromSettings(): \Illuminate\Support\Collection
    {
        $keys = collect(self::CASH_BANK_SETTING_KEYS)
            ->merge(
                CoaSetting::query()
                    ->where('key', 'like', '%cash_account%')
                    ->pluck('key')
            )
            ->unique()
            ->values();

        return CoaSetting::query()
            ->whereIn('key', $keys)
            ->whereNotNull('account_id')
            ->pluck('account_id')
            ->unique()
            ->values();
    }

    /**
     * @return array<int, mixed>
     */
    public static function cashBankIdValidationRules(): array
    {
        $ids = static::cashBankOptions()->pluck('id')->all();

        if ($ids === []) {
            return ['required', 'integer', 'exists:accounts,id'];
        }

        return ['required', 'integer', Rule::in($ids)];
    }

    public static function defaultCashBankAccount(): ?self
    {
        $coa = app(\App\ERP\Accounting\Services\CoaSettingService::class);

        try {
            return $coa->resolveAccountByKey('project_invoice_cash_account');
        } catch (\Throwable) {
            return static::cashBankOptions()->first();
        }
    }

    public function displayLabel(): string
    {
        return $this->code.' - '.$this->name;
    }

    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
        ];
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
