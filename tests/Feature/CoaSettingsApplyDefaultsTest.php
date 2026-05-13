<?php

namespace Tests\Feature;

use App\ERP\Accounting\Models\Account;
use App\ERP\Accounting\Models\CoaSetting;
use App\Http\Middleware\ErpMaintenanceMode;
use App\Http\Middleware\LogErpActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Tests\TestCase;

class CoaSettingsApplyDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_defaults_creates_sales_channel_admin_accounts_and_settings(): void
    {
        $this->withoutMiddleware([
            ErpMaintenanceMode::class,
            LogErpActivity::class,
            RoleMiddleware::class,
            RoleOrPermissionMiddleware::class,
        ]);

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('erp.accounting.coa-settings.apply-defaults'))
            ->assertRedirect();

        $payable = Account::query()->where('code', '2090')->firstOrFail();
        $expense = Account::query()->where('code', '5016')->firstOrFail();

        $this->assertSame('liability', $payable->type);
        $this->assertSame('expense', $expense->type);
        $this->assertTrue((bool) $payable->is_active);
        $this->assertTrue((bool) $expense->is_active);

        $this->assertDatabaseHas('accounting_coa_settings', [
            'key' => 'pos_sale_sales_channel_admin_payable',
            'account_id' => $payable->id,
        ]);
        $this->assertDatabaseHas('accounting_coa_settings', [
            'key' => 'pos_sale_sales_channel_admin_expense',
            'account_id' => $expense->id,
        ]);

        $this->assertSame(2, CoaSetting::query()
            ->whereIn('key', [
                'pos_sale_sales_channel_admin_payable',
                'pos_sale_sales_channel_admin_expense',
            ])
            ->count());
    }
}
