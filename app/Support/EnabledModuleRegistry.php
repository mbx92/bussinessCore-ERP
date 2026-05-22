<?php

namespace App\Support;

final class EnabledModuleRegistry
{
    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function installableModules(): array
    {
        return [
            'accounting' => ['label' => 'Accounting', 'description' => 'Cashflow, COA, rekonsiliasi, pembayaran, dan utilitas akuntansi.'],
            'sales' => ['label' => 'Sales', 'description' => 'POS, transaksi penjualan, dan invoice project.'],
            'purchasing' => ['label' => 'Purchasing', 'description' => 'Supplier, purchase order, goods receipt, dan reorder planning.'],
            'inventory' => ['label' => 'Inventory', 'description' => 'Master produk, warehouse, stok, mutasi, opname, dan report stok.'],
            'projects' => ['label' => 'Projects', 'description' => 'Daftar project, budgeting, task, material, dan pembagian tim.'],
            'rnd' => ['label' => 'R&D', 'description' => 'Eksperimen, riset, budget, pembelian, dan output produk.'],
            'hr' => ['label' => 'HR', 'description' => 'Data karyawan dan dokumen legal.'],
            'crm' => ['label' => 'CRM', 'description' => 'Lead, customer, pipeline, dan aktivitas follow-up.'],
            'calendar' => ['label' => 'Calendar', 'description' => 'Kalender aktivitas ERP lintas modul.'],
            'reporting' => ['label' => 'Reporting', 'description' => 'General ledger, neraca saldo, profit project, dan rekap bulanan.'],
            'cms' => ['label' => 'Website CMS', 'description' => 'Landing site, media library, dan CMS publik.'],
            'personal' => ['label' => 'Personal', 'description' => 'Workspace keuangan personal pengguna.'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function allModuleKeys(): array
    {
        return array_keys(self::installableModules());
    }

    public static function moduleForRouteName(?string $routeName): ?string
    {
        if (! is_string($routeName) || $routeName === '') {
            return null;
        }

        $map = [
            'accounting' => ['erp.accounting', 'cash-in.', 'cash-out.'],
            'sales' => ['erp.sales.'],
            'purchasing' => ['erp.purchasing.'],
            'inventory' => ['erp.inventory.', 'erp.master-products.'],
            'projects' => ['erp.projects.', 'projects.', 'team-distribution.', 'project-payments.', 'referrals.'],
            'rnd' => ['rnd.'],
            'hr' => ['erp.hr.'],
            'crm' => ['erp.crm.'],
            'calendar' => ['erp.calendar'],
            'reporting' => ['erp.reporting', 'reports.', 'export.'],
            'cms' => ['erp.cms.'],
            'personal' => ['personal'],
        ];

        foreach ($map as $moduleKey => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($routeName === rtrim($prefix, '.') || str_starts_with($routeName, $prefix)) {
                    return $moduleKey;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $menus
     * @param  list<string>  $enabledModuleKeys
     * @return list<array<string, mixed>>
     */
    public static function filterMenus(array $menus, array $enabledModuleKeys): array
    {
        return array_values(array_filter($menus, function (array $menu) use ($enabledModuleKeys): bool {
            $routeName = $menu['route'] ?? null;
            $moduleKey = self::moduleForRouteName(is_string($routeName) ? $routeName : null);

            return $moduleKey === null || in_array($moduleKey, $enabledModuleKeys, true);
        }));
    }
}
