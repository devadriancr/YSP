<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Roles and what each one can do (config/permissions.php). Admins only (see routes).
 * The administrator role is not edited: it always has every permission.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        return view('pages.roles.index', [
            'title' => 'Roles',
            'roles' => Role::query()->withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('pages.roles.form', ['title' => 'Nuevo rol', 'role' => new Role(['permissions' => []])]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $role = new Role($request->roleData());
        $role->key = $this->newKey($role->name);
        $role->save();

        return redirect()->route('roles.index')->with('status', __('Rol :name creado.', ['name' => $role->name]));
    }

    public function edit(Role $role): View
    {
        abort_if($role->isAdmin(), 403);

        return view('pages.roles.form', ['title' => 'Editar rol', 'role' => $role]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->isAdmin(), 403);
        $role->update($request->roleData());

        return redirect()->route('roles.index')->with('status', __('Rol :name actualizado.', ['name' => $role->name]));
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->isAdmin(), 403);

        if ($role->isBuiltIn()) {
            return back()->with('error', __('Los roles originales del portal no se pueden eliminar.'));
        }
        if ($role->users()->exists()) {
            return back()->with('error', __('Hay usuarios con este rol: cámbialos de rol antes de eliminarlo.'));
        }

        $role->delete();

        return redirect()->route('roles.index')->with('status', __('Rol :name eliminado.', ['name' => $role->name]));
    }

    /** Short unique key for users.role ("Cadena de suministro" → cadena_de_sumini). */
    private function newKey(string $name): string
    {
        $base = Str::limit(Str::slug($name, '_'), 16, '') ?: 'rol';
        $key = $base;
        for ($i = 2; Role::where('key', $key)->exists(); $i++) {
            $key = $base.'_'.$i;
        }

        return $key;
    }
}
