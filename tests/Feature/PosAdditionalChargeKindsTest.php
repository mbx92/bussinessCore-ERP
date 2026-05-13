<?php

namespace Tests\Feature;

use App\ERP\Accounting\Models\Account;
use App\ERP\Accounting\Models\JournalEntry;
use App\ERP\Inventory\Models\Warehouse;
use App\Http\Middleware\ErpMaintenanceMode;
use App\Http\Middleware\LogErpActivity;
use App\Models\MasterProduct;
use App\Models\PaymentMethod;
use App\Models\PosSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class PosAdditionalChargeKindsTest extends TestCase
{
    use RefreshDatabase;

    private function seedPosAccountsAndWarehouse(): array
    {
        $user = User::factory()->create();
        $paymentMethod = PaymentMethod::query()->create([
            'code' => 'cash',
            'name' => 'Cash',
            'status' => 'active',
        ]);
        Warehouse::query()->create([
            'code' => 'WH-POS',
            'name' => 'POS',
            'is_active' => true,
        ]);
        foreach (
            [
                ['code' => '1001', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit'],
                ['code' => '2090', 'name' => 'Hutang Channel', 'type' => 'liability', 'normal_balance' => 'credit'],
                ['code' => '4002', 'name' => 'Penjualan POS', 'type' => 'revenue', 'normal_balance' => 'credit'],
                ['code' => '4004', 'name' => 'Pendapatan Lain-lain', 'type' => 'revenue', 'normal_balance' => 'credit'],
                ['code' => '5016', 'name' => 'Beban Admin Channel', 'type' => 'expense', 'normal_balance' => 'debit'],
            ] as $row
        ) {
            Account::query()->updateOrCreate(
                ['code' => $row['code']],
                $row + ['is_active' => true]
            );
        }

        $product = MasterProduct::query()->create([
            'sku' => 'POS-FEE-001',
            'name' => 'Produk Fee Test',
            'category' => 'General',
            'uom' => 'pcs',
            'sales_channel' => 'pos',
            'product_type' => 'finished_goods',
            'status' => 'active',
            'selling_price' => 10000,
            'stock' => 5,
        ]);

        return [$user, $paymentMethod, $product];
    }

    public function test_journal_admin_fee_does_not_increase_grand_total_but_posts_expense(): void
    {
        $this->withoutMiddleware([
            ErpMaintenanceMode::class,
            LogErpActivity::class,
            RoleMiddleware::class,
        ]);

        [$user, $paymentMethod, $product] = $this->seedPosAccountsAndWarehouse();

        $response = $this->actingAs($user)->postJson(route('erp.sales.pos.checkout'), [
            'sales_channel' => 'retail',
            'payment_method_id' => $paymentMethod->id,
            'cash_paid' => 10000,
            'additional_charges' => [
                ['name' => 'Komisi marketplace', 'amount' => 1000, 'kind' => 'journal_admin'],
            ],
            'items' => [[
                'master_product_id' => $product->id,
                'sku' => $product->sku,
                'uom' => 'pcs',
                'qty' => 1,
                'unit_price' => 10000,
                'discount_percent' => 0,
                'multiplier' => 1,
                'price_operation' => 'multiply',
            ]],
        ]);

        $response->assertOk()->assertJsonPath('grand_total', 10000);

        $sale = PosSale::query()->latest('id')->firstOrFail();
        $this->assertSame(10000.0, (float) $sale->grand_total);
        $this->assertSame(0.0, (float) $sale->additional_fee);
        $this->assertSame(1000.0, (float) $sale->sales_channel_admin_fee);

        $entry = JournalEntry::query()->where('source_module', 'pos_sale')->where('source_reference', $sale->number)->firstOrFail();
        $entry->load('lines');
        $this->assertEqualsWithDelta(
            (float) $entry->lines->sum('debit'),
            (float) $entry->lines->sum('credit'),
            0.01,
            'Journal must balance'
        );
    }

    public function test_add_to_total_and_journal_admin_combined(): void
    {
        $this->withoutMiddleware([
            ErpMaintenanceMode::class,
            LogErpActivity::class,
            RoleMiddleware::class,
        ]);

        [$user, $paymentMethod, $product] = $this->seedPosAccountsAndWarehouse();

        $response = $this->actingAs($user)->postJson(route('erp.sales.pos.checkout'), [
            'sales_channel' => 'retail',
            'payment_method_id' => $paymentMethod->id,
            'cash_paid' => 10500,
            'additional_charges' => [
                ['name' => 'Ongkir', 'amount' => 500, 'kind' => 'add_to_total'],
                ['name' => 'Admin channel', 'amount' => 1000, 'kind' => 'journal_admin'],
            ],
            'items' => [[
                'master_product_id' => $product->id,
                'sku' => $product->sku,
                'uom' => 'pcs',
                'qty' => 1,
                'unit_price' => 10000,
                'discount_percent' => 0,
                'multiplier' => 1,
                'price_operation' => 'multiply',
            ]],
        ]);

        $response->assertOk()->assertJsonPath('grand_total', 10500);

        $sale = PosSale::query()->latest('id')->firstOrFail();
        $this->assertSame(10500.0, (float) $sale->grand_total);
        $this->assertSame(500.0, (float) $sale->additional_fee);
        $this->assertSame(1000.0, (float) $sale->sales_channel_admin_fee);
    }

    public function test_rejects_admin_fee_greater_than_net_sales(): void
    {
        $this->withoutMiddleware([
            ErpMaintenanceMode::class,
            LogErpActivity::class,
            RoleMiddleware::class,
        ]);

        [$user, $paymentMethod, $product] = $this->seedPosAccountsAndWarehouse();

        $response = $this->actingAs($user)->postJson(route('erp.sales.pos.checkout'), [
            'sales_channel' => 'retail',
            'payment_method_id' => $paymentMethod->id,
            'cash_paid' => 10000,
            'additional_charges' => [
                ['name' => 'Admin besar', 'amount' => 10001, 'kind' => 'journal_admin'],
            ],
            'items' => [[
                'master_product_id' => $product->id,
                'sku' => $product->sku,
                'uom' => 'pcs',
                'qty' => 1,
                'unit_price' => 10000,
                'discount_percent' => 0,
                'multiplier' => 1,
                'price_operation' => 'multiply',
            ]],
        ]);

        $response->assertStatus(422);
    }
}
