<?php

namespace App\Http\Middleware;

use App\Models\ErpSetting;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ErpMaintenanceMode
{
    private const MAINTENANCE_ROUTE = 'erp/admin/maintenance-mode';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');

        if (! $this->pathMatchesErpScope($path)) {
            return $next($request);
        }

        if ($this->isMaintenanceSettingsPath($path)) {
            return $next($request);
        }

        if ($request->user()->hasRole('admin')) {
            return $next($request);
        }

        $setting = ErpSetting::query()->first();

        if ($setting?->maintenance_global_enabled) {
            return $this->maintenanceResponse(
                $request,
                'Maintenance — seluruh ERP',
                $setting->maintenance_global_message,
            );
        }

        $module = $this->moduleKeyForPath($path);
        if ($module !== null && $this->moduleIsUnderMaintenance($setting, $module)) {
            $msg = $this->moduleMessage($setting, $module);

            return $this->maintenanceResponse(
                $request,
                'Maintenance — modul '.self::moduleLabel($module),
                $msg,
            );
        }

        return $next($request);
    }

    private function pathMatchesErpScope(string $path): bool
    {
        return str_starts_with($path, 'erp/')
            || $path === 'projects'
            || str_starts_with($path, 'projects/')
            || str_starts_with($path, 'kas-masuk')
            || str_starts_with($path, 'kas-keluar')
            || str_starts_with($path, 'laporan/')
            || str_starts_with($path, 'project-payments')
            || str_starts_with($path, 'team-distribution')
            || str_starts_with($path, 'export/')
            || $path === 'referrals'
            || str_starts_with($path, 'referrals/');
    }

    private function isMaintenanceSettingsPath(string $path): bool
    {
        return str_starts_with($path, self::MAINTENANCE_ROUTE);
    }

    private function moduleKeyForPath(string $path): ?string
    {
        if (str_starts_with($path, 'erp/accounting') || str_starts_with($path, 'kas-masuk') || str_starts_with($path, 'kas-keluar')) {
            return 'accounting';
        }
        if (str_starts_with($path, 'erp/sales')) {
            return 'sales';
        }
        if (str_starts_with($path, 'erp/purchasing')) {
            return 'purchasing';
        }
        if (str_starts_with($path, 'erp/inventory') || str_starts_with($path, 'erp/master-products')) {
            return 'inventory';
        }
        if (str_starts_with($path, 'erp/projects') || str_starts_with($path, 'projects/')
            || $path === 'projects' || str_starts_with($path, 'project-payments')
            || str_starts_with($path, 'referrals') || str_starts_with($path, 'team-distribution')) {
            return 'projects';
        }
        if (str_starts_with($path, 'erp/hr')) {
            return 'hr';
        }
        if (str_starts_with($path, 'erp/reporting') || str_starts_with($path, 'laporan/') || str_starts_with($path, 'export/')) {
            return 'reporting';
        }
        if (str_starts_with($path, 'erp/admin') || str_starts_with($path, 'erp/administration')) {
            return 'administration';
        }

        return null;
    }

    private function moduleIsUnderMaintenance(?ErpSetting $setting, string $module): bool
    {
        if (! $setting instanceof ErpSetting) {
            return false;
        }
        $merged = $setting->mergedMaintenanceModules();
        $row = $merged[$module] ?? null;
        if (! is_array($row)) {
            return false;
        }

        return ErpSetting::coerceMaintenanceEnabled($row['enabled'] ?? false);
    }

    private function moduleMessage(?ErpSetting $setting, string $module): ?string
    {
        if (! $setting instanceof ErpSetting) {
            return null;
        }
        $merged = $setting->mergedMaintenanceModules();
        $row = $merged[$module] ?? null;
        if (! is_array($row)) {
            return null;
        }
        $m = $row['message'] ?? null;

        return is_string($m) && trim($m) !== '' ? trim($m) : null;
    }

    private static function moduleLabel(string $key): string
    {
        return match ($key) {
            'accounting' => 'Accounting',
            'sales' => 'Sales',
            'purchasing' => 'Purchasing',
            'inventory' => 'Inventory',
            'projects' => 'Projects',
            'hr' => 'HR',
            'reporting' => 'Reporting',
            'administration' => 'Administration',
            default => $key,
        };
    }

    private function maintenanceResponse(Request $request, string $title, ?string $message): Response
    {
        return Inertia::render('Maintenance', [
            'title' => $title,
            'message' => $message,
        ])->toResponse($request)->setStatusCode(503);
    }
}
