<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentMethodSalesChannelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
    }

    public function test_payment_method_can_have_multiple_sales_channels(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('erp.admin.payment-methods.store'), [
                'code' => 'qris-mp',
                'name' => 'QRIS Marketplace',
                'description' => null,
                'sales_channels' => ['marketplace', 'online'],
                'status' => 'active',
            ])
            ->assertRedirect();

        $method = PaymentMethod::query()->where('code', 'qris-mp')->first();
        $this->assertNotNull($method);
        $this->assertSame(['marketplace', 'online'], $method->salesChannelsList());
    }

    public function test_payment_method_availability_respects_assigned_channels(): void
    {
        $method = PaymentMethod::query()->create([
            'code' => 'retail-only',
            'name' => 'Retail Only',
            'status' => 'active',
        ]);
        $method->syncSalesChannels(['retail']);

        $this->assertTrue($method->isAvailableForSalesChannel('retail'));
        $this->assertFalse($method->isAvailableForSalesChannel('grosir'));
        $this->assertFalse($method->isAvailableForSalesChannel('marketplace'));
    }

    public function test_payment_method_without_channels_is_available_on_all_channels(): void
    {
        $method = PaymentMethod::query()->create([
            'code' => 'legacy',
            'name' => 'Legacy',
            'status' => 'active',
        ]);

        $this->assertTrue($method->isAvailableForSalesChannel('retail'));
        $this->assertTrue($method->isAvailableForSalesChannel('grosir'));
    }
}
