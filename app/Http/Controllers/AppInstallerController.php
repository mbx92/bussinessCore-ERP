<?php

namespace App\Http\Controllers;

use App\ERP\Core\Models\Company;
use App\ERP\Core\Models\Currency;
use App\ERP\Core\Models\DocumentSequence;
use App\ERP\Core\Models\FiscalPeriod;
use App\Models\ErpSetting;
use App\Models\User;
use App\Services\AppInstallationService;
use App\Services\ModuleLifecycleManager;
use App\Support\EnabledModuleRegistry;
use App\Support\ModulePermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia as InertiaFacade;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppInstallerController extends Controller
{
    public function __construct(
        private readonly AppInstallationService $installationService,
        private readonly ModuleLifecycleManager $moduleLifecycleManager,
    )
    {
    }

    public function create(): Response|RedirectResponse
    {
        $previewRequested = App::environment('local')
            && in_array(request()->query('preview'), ['loading', 'error'], true);

        if ($this->installationService->status()['installed'] && ! $previewRequested) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Installer/Setup', [
            'moduleOptions' => collect(EnabledModuleRegistry::installableModules())
                ->map(fn (array $meta, string $key): array => ['key' => $key, ...$meta])
                ->sortBy('label')
                ->values()
                ->all(),
            'defaults' => [
                'app_name' => 'BusinessCore ERP',
                'app_tagline' => 'Business Operating Platform',
                'company_name' => 'BusinessCore',
                'company_legal_name' => 'PT BusinessCore Indonesia',
                'modules' => ['accounting', 'sales', 'purchasing', 'inventory', 'projects', 'reporting'],
                'db_connection' => config('database.default', 'pgsql'),
                'db_host' => config('database.connections.'.config('database.default').'.host', '127.0.0.1'),
                'db_port' => (string) config('database.connections.'.config('database.default').'.port', '5432'),
                'db_database' => (string) config('database.connections.'.config('database.default').'.database', ''),
                'db_username' => (string) config('database.connections.'.config('database.default').'.username', ''),
                'db_password' => '',
            ],
        ]);
    }

    public function complete(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->get('installer_completed')) {
            return redirect()->route('install.show');
        }

        return Inertia::render('Installer/Complete', [
            'loginUrl' => route('login'),
        ]);
    }

    public function testConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'db_connection' => ['required', Rule::in(['pgsql', 'mysql', 'sqlite'])],
            'db_host' => ['nullable', 'string', 'max:255'],
            'db_port' => ['nullable', 'string', 'max:20'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('db_connection') !== 'sqlite')],
            'db_password' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('db_connection') !== 'sqlite')],
        ]);

        $result = $this->installationService->testDatabaseConnection([
            'connection' => $validated['db_connection'],
            'host' => $validated['db_host'] ?? null,
            'port' => $validated['db_port'] ?? null,
            'database' => $validated['db_database'],
            'username' => $validated['db_username'] ?? null,
            'password' => $validated['db_password'] ?? null,
        ]);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function store(Request $request): JsonResponse|RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        if ($this->installationService->status()['installed']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Aplikasi sudah terpasang.',
                    'next_url' => route('dashboard'),
                ]);
            }

            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'db_connection' => ['required', Rule::in(['pgsql', 'mysql', 'sqlite'])],
            'db_host' => ['nullable', 'string', 'max:255'],
            'db_port' => ['nullable', 'string', 'max:20'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('db_connection') !== 'sqlite')],
            'db_password' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('db_connection') !== 'sqlite')],
            'app_name' => ['required', 'string', 'max:120'],
            'app_tagline' => ['nullable', 'string', 'max:190'],
            'company_name' => ['required', 'string', 'max:120'],
            'company_legal_name' => ['nullable', 'string', 'max:190'],
            'company_tax_id' => ['nullable', 'string', 'max:60'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'string', 'email', 'max:255'],
            'admin_password' => ['required', 'confirmed', Password::defaults()],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => ['string', Rule::in(EnabledModuleRegistry::allModuleKeys())],
        ]);

        $databaseConfig = [
            'connection' => $validated['db_connection'],
            'host' => $validated['db_host'] ?? null,
            'port' => $validated['db_port'] ?? null,
            'database' => $validated['db_database'],
            'username' => $validated['db_username'] ?? null,
            'password' => $validated['db_password'] ?? null,
        ];

        try {
            $this->installationService->applyDatabaseConfig($databaseConfig);
            $this->installationService->runMigrations();
        } catch (\Throwable $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Koneksi database atau migrasi gagal: '.$exception->getMessage(),
                    'errors' => [
                        'db_connection' => ['Koneksi database atau migrasi gagal.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'db_connection' => 'Koneksi database atau migrasi gagal: '.$exception->getMessage(),
            ])->withInput();
        }

        DB::transaction(function () use ($validated): void {
            $this->ensureRolesAndPermissions();
            $this->ensureBaseData();

            Company::query()->firstOrCreate(
                ['name' => $validated['company_name']],
                [
                    'legal_name' => $validated['company_legal_name'] ?: $validated['company_name'],
                    'tax_id' => $validated['company_tax_id'] ?: null,
                    'is_active' => true,
                ],
            );

            $setting = ErpSetting::query()->firstOrCreate([], [
                'app_name' => 'BusinessCore ERP',
                'app_tagline' => 'Business Operating Platform',
            ]);

            $setting->fill([
                'app_name' => $validated['app_name'],
                'app_tagline' => $validated['app_tagline'] ?: null,
                'enabled_modules' => array_values(array_unique($validated['modules'])),
                'installed_at' => now(),
            ])->save();

            $admin = User::query()->updateOrCreate(
                ['email' => $validated['admin_email']],
                [
                    'name' => $validated['admin_name'],
                    'password' => $validated['admin_password'],
                ],
            );

            $admin->syncRoles(['admin']);
        });

        $this->moduleLifecycleManager->markInstalledAndEnabled($validated['modules']);

        $this->installationService->persistDatabaseConfig($databaseConfig);

        $request->session()->put('installer_completed', true);
        $request->session()->flash('status', 'Setup selesai. Silakan login dengan akun administrator yang baru dibuat.');

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'next_url' => route('install.complete'),
            ]);
        }

        return InertiaFacade::location(route('install.complete'));
    }

    private function ensureRolesAndPermissions(): void
    {
        $roles = ['admin', 'manajer', 'finance', 'sales', 'purchasing', 'inventory', 'hr', 'project', 'anggota'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $permissions = [
            'erp.core.manage',
            'erp.accounting.post-journal',
            'erp.sales.manage',
            'erp.purchasing.manage',
            'erp.inventory.manage',
            'erp.hr.manage',
            'erp.project.manage',
            'manage-rnd',
            'erp.reporting.view',
            'erp.period.close',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $registeredPermissionNames = ModulePermissionRegistry::permissionNames();

        foreach ($registeredPermissionNames as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $menuPermissionNames = collect(ModulePermissionRegistry::menuDefinitions())
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && $name !== '')
            ->values()
            ->all();

        $adminPermissions = array_values(array_unique(array_merge($permissions, $registeredPermissionNames, $menuPermissionNames)));
        Role::findByName('admin')->syncPermissions($adminPermissions);
    }

    private function ensureBaseData(): void
    {
        Currency::query()->firstOrCreate(
            ['code' => 'IDR'],
            ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'is_base' => true],
        );

        FiscalPeriod::query()->firstOrCreate(
            ['name' => now()->format('Y')],
            ['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'is_closed' => false],
        );

        $documentSequences = [
            ['module' => 'sales', 'document_type' => 'project_invoice', 'prefix' => 'INV-PRJ', 'running_number' => 0, 'padding_length' => 6],
            ['module' => 'purchasing', 'document_type' => 'purchase_order', 'prefix' => 'PO', 'running_number' => 0, 'padding_length' => 6],
            ['module' => 'purchasing', 'document_type' => 'goods_receipt', 'prefix' => 'GRN', 'running_number' => 0, 'padding_length' => 6],
            ['module' => 'purchasing', 'document_type' => 'supplier_code', 'prefix' => 'SUP', 'running_number' => 0, 'padding_length' => 3],
            ['module' => 'accounting', 'document_type' => 'journal_entry', 'prefix' => 'JE', 'running_number' => 0, 'padding_length' => 6],
            ['module' => 'accounting', 'document_type' => 'payable_bill', 'prefix' => 'BILL', 'running_number' => 0, 'padding_length' => 6],
        ];

        foreach ($documentSequences as $sequence) {
            DocumentSequence::query()->updateOrCreate(
                ['module' => $sequence['module'], 'document_type' => $sequence['document_type']],
                $sequence,
            );
        }
    }
}
