<?php

namespace Tests\Feature;

use App\ERP\Purchasing\Models\Vendor;
use App\Http\Middleware\ErpMaintenanceMode;
use App\Http\Middleware\LogErpActivity;
use App\Models\MasterProduct;
use App\Models\RndProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RndModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            ErpMaintenanceMode::class,
            LogErpActivity::class,
        ]);

        Permission::firstOrCreate(['name' => 'manage-rnd', 'guard_name' => 'web']);
    }

    public function test_rnd_routes_require_authentication_and_permission(): void
    {
        $project = RndProject::query()->create([
            'name' => 'Riset Material Baru',
            'category' => 'Material',
            'status' => 'idea',
        ]);

        $this->get(route('rnd.dashboard'))->assertRedirect(route('login'));

        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission)
            ->get(route('rnd.projects.show', $project))
            ->assertForbidden();

        $userWithPermission = $this->rndUser();
        $this->actingAs($userWithPermission)
            ->get(route('rnd.projects.show', $project))
            ->assertOk();
    }

    public function test_project_crud_and_dashboard_render_with_inertia(): void
    {
        $user = $this->rndUser();

        $this->actingAs($user)
            ->post(route('rnd.projects.store'), [
                'name' => 'Formula Minuman A',
                'description' => 'Eksperimen batch awal',
                'category' => 'Formula',
                'status' => 'research',
                'pic_user_id' => $user->id,
                'start_date' => '2026-05-18',
                'notes' => 'Fokus pada rasa dan stabilitas.',
            ])
            ->assertRedirect();

        $project = RndProject::query()->firstOrFail();

        $this->assertDatabaseHas('rnd_projects', [
            'id' => $project->id,
            'name' => 'Formula Minuman A',
            'status' => 'research',
            'pic_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('rnd.projects.update', $project), [
                'name' => 'Formula Minuman A v2',
                'description' => 'Eksperimen revisi',
                'category' => 'Formula',
                'status' => 'development',
                'pic_user_id' => $user->id,
                'start_date' => '2026-05-19',
                'notes' => 'Revisi kadar gula.',
            ])
            ->assertRedirect(route('rnd.projects.show', $project));

        $this->actingAs($user)
            ->get(route('rnd.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rnd/Index')
                ->where('projects.data.0.name', 'Formula Minuman A v2')
                ->where('projects.data.0.status', 'development')
                ->etc());
    }

    public function test_notes_budget_purchases_outputs_and_report_are_calculated_correctly(): void
    {
        Storage::fake('public');

        $user = $this->rndUser();
        $project = RndProject::query()->create([
            'name' => 'Prototipe Sensor',
            'category' => 'Elektronik',
            'status' => 'development',
            'pic_user_id' => $user->id,
            'start_date' => '2026-05-18',
        ]);
        $product = $this->createProduct();
        $supplier = $this->createSupplier();

        $this->actingAs($user)
            ->post(route('rnd.projects.notes.store', $project), [
                'title' => 'Percobaan Awal',
                'content' => '<p>Aman</p><script>alert(1)</script><strong>Valid</strong>',
                'attachments' => [
                    UploadedFile::fake()->create('hasil-uji.pdf', 120, 'application/pdf'),
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('rnd_research_notes', [
            'rnd_project_id' => $project->id,
            'title' => 'Percobaan Awal',
            'created_by' => $user->id,
        ]);
        $this->assertDatabaseCount('rnd_research_note_attachments', 1);
        $this->assertStringNotContainsString(
            '<script>',
            (string) $project->researchNotes()->first()?->content
        );

        $this->actingAs($user)
            ->post(route('rnd.projects.budgets.store', $project), [
                'name' => 'PCB Custom',
                'qty' => 2,
                'estimated_unit_price' => 150000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('rnd_budget_items', [
            'rnd_project_id' => $project->id,
            'name' => 'PCB Custom',
            'total_price' => '300000.00',
        ]);

        $this->actingAs($user)
            ->post(route('rnd.projects.purchases.store', $project), [
                'master_product_id' => $product->id,
                'supplier_id' => $supplier->id,
                'qty' => 3,
                'unit_price' => 80000,
                'category' => 'alat',
                'purchase_date' => '2026-05-18',
                'notes' => 'Batch pertama',
                'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('rnd.projects.purchases.store', $project), [
                'master_product_id' => $product->id,
                'supplier_id' => $supplier->id,
                'qty' => 2,
                'unit_price' => 30000,
                'category' => 'bahan',
                'purchase_date' => '2026-05-19',
                'notes' => 'Bahan pendukung',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('rnd_purchases', [
            'rnd_project_id' => $project->id,
            'category' => 'alat',
            'total_price' => '240000.00',
        ]);
        $this->assertDatabaseHas('rnd_purchases', [
            'rnd_project_id' => $project->id,
            'category' => 'bahan',
            'total_price' => '60000.00',
        ]);

        $this->actingAs($user)
            ->post(route('rnd.projects.outputs.store', $project), [
                'name' => 'Prototype A',
                'description' => 'Unit pengujian 1',
                'units_produced' => 4,
                'notes' => 'Layak uji',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('rnd.projects.outputs.store', $project), [
                'name' => 'Prototype B',
                'description' => 'Unit pengujian 2',
                'units_produced' => 2,
                'notes' => 'Butuh revisi',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->get(route('rnd.projects.show', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rnd/Show')
                ->where('summary.estimated_budget_total', 300000)
                ->where('summary.actual_spend_total', 300000)
                ->where('summary.alat_total', 240000)
                ->where('summary.bahan_total', 60000)
                ->where('summary.units_produced_total', 6)
                ->where('summary.hpp_per_unit', 50000)
                ->where('outputs.0.hpp_per_unit', 50000)
                ->etc());

        $this->actingAs($user)
            ->get(route('rnd.projects.report', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rnd/Report')
                ->where('summary.hpp_per_unit', 50000)
                ->where('purchases.data.0.category', 'bahan')
                ->etc());

        $this->actingAs($user)
            ->get(route('rnd.projects.report.pdf', $project))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function rndUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-rnd');

        return $user;
    }

    private function createSupplier(): Vendor
    {
        return Vendor::query()->create([
            'code' => 'SUP-RND-001',
            'name' => 'Supplier Riset',
            'is_active' => true,
        ]);
    }

    private function createProduct(): MasterProduct
    {
        return MasterProduct::query()->create([
            'sku' => 'RND-PROD-001',
            'barcode' => 'RND-BC-001',
            'name' => 'Komponen Sensor',
            'category' => 'Elektronik',
            'uom' => 'pcs',
            'sales_channel' => 'project',
            'product_type' => 'project_material',
            'status' => 'active',
            'description' => 'Komponen untuk prototipe.',
            'selling_price' => 100000,
            'stock' => 10,
            'min_stock' => 1,
            'lead_time_days' => 7,
            'low_stock_alert_enabled' => true,
        ]);
    }
}
