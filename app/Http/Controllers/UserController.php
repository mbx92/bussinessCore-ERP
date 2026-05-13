<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function workspace()
    {
        return Inertia::render('Users/Workspace');
    }

    public function index(Request $request)
    {
        $users = User::with('roles')->orderBy('name')->paginate($this->resolvedPerPage($request))->withQueryString()
            ->through(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->roles->first()?->name ?? '-',
            ]);

        $names = User::ASSIGNABLE_ROLE_NAMES;
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $names)
            ->get(['id', 'name'])
            ->sortBy(fn (Role $r) => array_search($r->name, $names, true))
            ->values();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $this->filtersWithPerPage($request, []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(User::ASSIGNABLE_ROLE_NAMES)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->assignRole($validated['role']);

        return back()->with('flash', ['type' => 'success', 'message' => 'User berhasil ditambahkan.']);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(User::ASSIGNABLE_ROLE_NAMES)],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        return back()->with('flash', ['type' => 'success', 'message' => 'User berhasil diperbarui.']);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'Tidak dapat menghapus akun sendiri.']);
        }
        $user->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'User berhasil dihapus.']);
    }
}
