<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::where('guard_name', 'web')
            ->orderByDesc('id')
            ->get(['id', 'name']);

        $roles = $roles->map(function ($role) {
            $role->permissions_count = $role->permissions()->count();
            return $role;
        });

        return response()->json($roles);
    }

    public function permissions()
    {
        $allPermissions = Permission::where('guard_name', 'web')
            ->pluck('name')
            ->toArray();

        $groupedPermissions = $this->groupPermissionsByResource($allPermissions);

        return response()->json([
            'allPermissions' => $groupedPermissions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => $role
        ]);
    }

    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        return response()->json([
            'role' => $role,
            'permissions' => $role->permissions->pluck('name')
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'permissions' => 'required|array',
        ]);

        $role = Role::findOrFail($id);
        $role->name = $request->name;
        $role->save();

        $role->syncPermissions($request->permissions);

        return response()->json([
            'message' => 'Role updated successfully.',
            'role' => $role
        ]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.'
        ]);
    }

    private function groupPermissionsByResource($permissions)
    {
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission);
            $module = $parts[0];
            $sub = array_slice($parts, 1);

            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }

            $label = implode(' ', array_map(function ($part) {
                return ucfirst(str_replace('-', ' ', $part));
            }, $sub));

            if (!$label) $label = ucfirst($module);

            $grouped[$module][] = [
                'name' => $permission,
                'label' => $label,
            ];
        }

        return $grouped;
    }
}
