<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use DataTables;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $roles = Role::where('guard_name', 'web')->orderByDesc('id');

            return DataTables::of($roles)
                ->addIndexColumn()
                ->addColumn('permissions_count', function ($row) {
                    if ($row->name === 'Super Admin') {
                        return '<span class="badge bg-success">All permissions</span>';
                    }
                    return '<span class="badge bg-success">' . $row->permissions()->count() . ' permissions</span>';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                                <i class="ri-more-fill"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item EditBtn" data-id="' . $row->id . '">
                                        <i class="ri-pencil-fill me-2"></i>Edit
                                    </button>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item deleteBtn"
                                        data-delete-url="' . route('role.delete', $row->id) . '"
                                        data-method="DELETE"
                                        data-table="#roleTable">
                                        <i class="ri-delete-bin-fill me-2"></i>Delete
                                    </button>
                                </li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['permissions_count', 'action'])
                ->make(true);
        }

        return view('admin.roles.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role
        ]);
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);

        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        $rolePermissions = $role->permissions()->pluck('name')->toArray();

        $groupedPermissions = $this->groupPermissionsByResource($allPermissions);
        $groupedRolePermissions = $this->groupPermissionsByResource($rolePermissions);

        return response()->json([
            'role' => $role,
            'allPermissions' => $groupedPermissions,
            'rolePermissions' => $groupedRolePermissions,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:roles,id',
            'name' => 'required|string|unique:roles,name,' . $request->id,
            'permissions' => 'required|array',
        ]);

        $role = Role::findOrFail($request->id);
        $role->name = $request->name;
        $role->save();

        $role->syncPermissions($request->permissions);

        return response()->json([
            'message' => 'Role updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully'
        ]);
    }

    private function groupPermissionsByResource($permissions)
    {
        $grouped = [];

        foreach ($permissions as $permission) {
            // Everything before first dot is module
            $parts = explode('.', $permission);
            $module = $parts[0]; // e.g., 'checklist', 'project'
            $sub = array_slice($parts, 1); // e.g., ['index'], ['service-job','store']

            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }

            // Build readable label
            $label = implode(' ', array_map(function($part) {
                return ucfirst(str_replace('-', ' ', $part));
            }, $sub));

            if (!$label) $label = ucfirst($module); // fallback if no sub-part

            $grouped[$module][] = [
                'name' => $permission,
                'label' => $label,
            ];
        }

        return $grouped;
    }

    public function permissions()
    {
        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        $groupedPermissions = $this->groupPermissionsByResource($allPermissions);

        return response()->json([
            'allPermissions' => $groupedPermissions
        ]);
    }
}