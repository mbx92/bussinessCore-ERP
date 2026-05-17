<?php

namespace Tests\Feature;

use App\ERP\Accounting\Models\Account;
use App\ERP\Accounting\Models\CoaSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CashBankAccountOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'accounting.cash_bank_account_codes' => ['1101', '1102'],
        ]);
    }

    public function test_cash_bank_options_use_config_codes_not_only_100x_prefix(): void
    {
        $kas = Account::query()->create([
            'code' => '1101',
            'name' => 'Kas Operasional',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);
        Account::query()->create([
            'code' => '1102',
            'name' => 'Bank BCA',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);
        Account::query()->create([
            'code' => '1001',
            'name' => 'Kas Lama (dev)',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $options = Account::cashBankOptions();

        $this->assertCount(2, $options);
        $this->assertTrue($options->contains('id', $kas->id));
        $this->assertSame(['1101', '1102'], $options->pluck('code')->sort()->values()->all());
    }

    public function test_cash_bank_options_from_coa_settings_when_config_empty(): void
    {
        config(['accounting.cash_bank_account_codes' => []]);

        $kas = Account::query()->create([
            'code' => '1101',
            'name' => 'Kas',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        CoaSetting::query()->create([
            'key' => 'project_invoice_cash_account',
            'account_id' => $kas->id,
        ]);

        $options = Account::cashBankOptions();

        $this->assertCount(1, $options);
        $this->assertSame('1101', $options->first()->code);
    }

    public function test_payments_page_lists_production_style_cash_accounts(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\ErpMaintenanceMode::class,
            \App\Http\Middleware\LogErpActivity::class,
            \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        Account::query()->create([
            'code' => '1101',
            'name' => 'Kas',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('erp.accounting.payments'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('cashAccounts', 1)
                ->where('cashAccounts.0.code', '1101'));
    }
}
