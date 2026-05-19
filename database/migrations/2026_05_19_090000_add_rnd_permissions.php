<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $manageRnd = Permission::firstOrCreate([
            'name' => 'manage-rnd',
            'guard_name' => 'web',
        ]);

        $menuRnd = Permission::firstOrCreate([
            'name' => 'menu.erp.rnd',
            'guard_name' => 'web',
        ]);

        foreach (['admin', 'manajer'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                continue;
            }

            $role->givePermissionTo([$manageRnd, $menuRnd]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['manage-rnd', 'menu.erp.rnd'])
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
