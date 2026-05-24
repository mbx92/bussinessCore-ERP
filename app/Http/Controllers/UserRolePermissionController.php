<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ModulePermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserRolePermissionController extends Controller
{
    public function index(Request $request)
    {
        $definitions = collect(ModulePermissionRegistry::menuDefinitions());
        $allowedNames = $definitions->pluck('name')->all();

        $names = User::ASSIGNABLE_ROLE_NAMES;
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $names)
            ->get(['id', 'name'])
            ->sortBy(fn (Role $r) => array_search($r->name, $names, true))
            ->values();
        $firstRoleId = $roles->first()?->id;
        $selectedRoleId = (int) $request->query('role', $firstRoleId ?: 0);
        if (! $roles->contains(fn (Role $r) => (int) $r->id === (int) $selectedRoleId)) {
            $selectedRoleId = $firstRoleId ?: 0;
        }

        $roleModel = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $names)
            ->whereKey($selectedRoleId)
            ->with('permissions')
            ->first();

        $allowedLookup = array_flip($allowedNames);
        $selectedPermissions = $roleModel
            ? $roleModel->permissions
                ->pluck('name')
                ->filter(fn (string $n) => isset($allowedLookup[$n]))
                ->values()
                ->all()
            : [];

        $menuByGroup = $definitions
            ->groupBy('group')
            ->map(fn ($items) => $items->values()->all())
            ->all();

        return Inertia::render('Users/RolesPermissions', [
            'roles' => $roles,
            'selectedRoleId' => $selectedRoleId,
            'menuByGroup' => $menuByGroup,
            'selectedPermissions' => $selectedPermissions,
            'allowedMenuPermissionNames' => $allowedNames,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        if (! in_array($role->name, User::ASSIGNABLE_ROLE_NAMES, true)) {
            abort(404);
        }

        $allowed = collect(ModulePermissionRegistry::menuDefinitions())->pluck('name')->all();

        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in($allowed)],
        ]);

        $incomingMenu = collect($validated['permissions'])->unique()->values();
        $nonMenu = $role->permissions->pluck('name')->reject(fn (string $n) => str_starts_with($n, 'menu.'));

        $role->syncPermissions($nonMenu->merge($incomingMenu)->unique()->values()->all());

        return back()->with('flash', ['type' => 'success', 'message' => 'Hak akses menu untuk role berhasil disimpan.']);
    }
}
