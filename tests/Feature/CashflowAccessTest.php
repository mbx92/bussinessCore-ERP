<?php

namespace Tests\Feature;

use App\ERP\Accounting\Models\Account;
use App\Http\Middleware\ErpMaintenanceMode;
use App\Http\Middleware\LogErpActivity;
use App\Models\CashCategory;
use App\Models\CashIn;
use App\Models\CategoryCoaMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashflowAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_menu_permission_can_open_cashflow_page(): void
    {
        $this->withoutMiddleware([
            ErpMaintenanceMode::class,
            LogErpActivity::class,
        ]);

        Permission::firstOrCreate(['name' => 'menu.erp.accounting', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('menu.erp.accounting');

        Account::query()->create([
            'code' => '1001',
            'name' => 'Kas',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get(route('erp.accounting.cashflow'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ERP/Accounting/Cashflow')
                ->has('entries')
                ->has('totals')
                ->where('canMutate', false));
    }

    public function test_legacy_hyphenated_cashflow_path_redirects(): void
    {
        $this->withoutMiddleware([
            ErpMaintenanceMode::class,
            LogErpActivity::class,
        ]);

        Permission::firstOrCreate(['name' => 'menu.erp.accounting', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('menu.erp.accounting');

        Account::query()->create([
            'code' => '1001',
            'name' => 'Kas',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get('/erp/accounting/cash-flow?type=in')
            ->assertRedirect(route('erp.accounting.cashflow', ['type' => 'in']));
    }

    public function test_reporting_view_permission_can_open_cashflow_read_only(): void
    {
        $this->withoutMiddleware([
            ErpMaintenanceMode::class,
            LogErpActivity::class,
        ]);

        Permission::firstOrCreate(['name' => 'erp.reporting.view', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('erp.reporting.view');

        Account::query()->create([
            'code' => '1001',
            'name' => 'Kas',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get(route('erp.accounting.cashflow'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ERP/Accounting/Cashflow')
                ->where('canMutate', false));
    }

    public function test_finance_role_can_open_cashflow(): void
    {
        $this->withoutMiddleware([
            ErpMaintenanceMode::class,
            LogErpActivity::class,
        ]);

        Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'erp.accounting.post-journal', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('finance');
        $user->givePermissionTo('erp.accounting.post-journal');

        Account::query()->create([
            'code' => '1001',
            'name' => 'Kas',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get(route('erp.accounting.cashflow'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ERP/Accounting/Cashflow')
                ->where('canMutate', true));
    }

    public function test_manual_equity_cash_in_can_be_saved_without_project(): void
    {
        $this->withoutMiddleware([
            ErpMaintenanceMode::class,
            LogErpActivity::class,
        ]);

        Permission::firstOrCreate(['name' => 'erp.accounting.post-journal', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('erp.accounting.post-journal');

        $cashAccount = Account::query()->create([
            'code' => '1001',
            'name' => 'Kas',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $equityAccount = Account::query()->create([
            'code' => '3001',
            'name' => 'Modal Pemilik',
            'type' => 'equity',
            'normal_balance' => 'credit',
            'is_active' => true,
        ]);

        CashCategory::query()->create([
            'domain' => 'cash_in',
            'key' => 'investasi_masuk',
            'label' => 'Investasi / Setoran Modal',
            'is_active' => true,
            'sort_order' => 50,
        ]);

        CategoryCoaMapping::query()->create([
            'domain' => 'cash_in',
            'category' => 'investasi_masuk',
            'account_id' => $equityAccount->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('erp.accounting.cashflow.store'), [
                'type' => 'in',
                'project_id' => '',
                'cash_account_id' => $cashAccount->id,
                'payment_method_id' => null,
                'category' => 'investasi_masuk',
                'amount' => 17000000,
                'date' => '2025-10-01',
                'note' => 'Modal Awal Barang',
                'recipient_name' => '',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $cashIn = CashIn::query()->where('category', 'investasi_masuk')->firstOrFail();

        $this->assertNull($cashIn->project_id);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $cashIn->journal_entry_id,
            'account_id' => $cashAccount->id,
            'debit' => '17000000.00',
            'credit' => '0.00',
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $cashIn->journal_entry_id,
            'account_id' => $equityAccount->id,
            'debit' => '0.00',
            'credit' => '17000000.00',
        ]);
    }
}
