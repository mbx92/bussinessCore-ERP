<?php

namespace Tests\Feature;

use App\Models\ErpSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppInstallerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_root_redirects_to_installer_when_app_is_not_installed(): void
    {
        $this->get('/')
            ->assertRedirect(route('install.show'));
    }

    public function test_installer_page_is_accessible_before_setup(): void
    {
        $this->get(route('install.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Installer/Setup')
                ->has('moduleOptions')
                ->where('defaults.app_name', 'BusinessCore ERP'));
    }

    public function test_installer_can_create_initial_setup_and_admin_account(): void
    {
        $connection = config('database.default');

        $response = $this->post(route('install.store'), [
            'db_connection' => $connection,
            'db_host' => config("database.connections.{$connection}.host"),
            'db_port' => (string) config("database.connections.{$connection}.port"),
            'db_database' => (string) config("database.connections.{$connection}.database"),
            'db_username' => config("database.connections.{$connection}.username"),
            'db_password' => config("database.connections.{$connection}.password"),
            'app_name' => 'BusinessCore ERP',
            'app_tagline' => 'Business Operating Platform',
            'company_name' => 'BusinessCore',
            'company_legal_name' => 'PT BusinessCore Indonesia',
            'company_tax_id' => '01.234.567.8-999.000',
            'admin_name' => 'Owner',
            'admin_email' => 'owner@businesscore.test',
            'admin_password' => 'Password123!',
            'admin_password_confirmation' => 'Password123!',
            'modules' => ['accounting', 'sales', 'inventory'],
        ]);

        $response->assertRedirect(route('install.complete'));

        $this->assertDatabaseHas('erp_settings', [
            'app_name' => 'BusinessCore ERP',
        ]);

        $setting = ErpSetting::query()->firstOrFail();
        $this->assertNotNull($setting->installed_at);
        $this->assertSame(['accounting', 'sales', 'inventory'], $setting->enabledModuleKeys());

        $admin = User::query()->where('email', 'owner@businesscore.test')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_disabled_module_route_redirects_back_to_dashboard(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        ErpSetting::query()->create([
            'app_name' => 'BusinessCore ERP',
            'app_tagline' => 'Business Operating Platform',
            'enabled_modules' => ['accounting'],
            'installed_at' => now(),
        ]);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('erp.sales'))
            ->assertRedirect(route('dashboard'));
    }
}
