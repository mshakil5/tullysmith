<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = User::select(['id', 'name', 'email', 'phone', 'primary_contact', 'address', 'status'])
            ->where('user_type', 1)
            ->with('roles')
            ->orderByDesc('id');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%')
                ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $employees = $query->paginate(15);

        $employees->getCollection()->transform(function ($user) {
            $user->role    = $user->getRoleNames()->first() ?? '-';
            $user->role_id = $user->roles()->first()?->id;
            return $user;
        });

        $roles = Role::select('id', 'name')->orderBy('name')->get();

        return response()->json([
            'data'      => $employees->items(),
            'last_page' => $employees->lastPage(),
            'total'     => $employees->total(),
            'roles'     => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'primary_contact' => 'nullable|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'phone'           => 'required|string|max:20',
            'address'         => 'nullable|string|max:500',
            'password'        => 'required|string|min:6',
            'role_id'         => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name'            => $request->name,
            'primary_contact' => $request->primary_contact,
            'email'           => $request->email,
            'phone'           => $request->phone,
            'address'         => $request->address,
            'password'        => Hash::make($request->password),
            'user_type'       => 1,
            'status'          => 1,
        ]);

        $role = Role::find($request->role_id);
        $user->syncRoles($role);

        return response()->json(['message' => 'Employee created successfully.', 'employee' => $user], 201);
    }

    public function show($id)
    {
        $user = User::where('user_type', 1)->findOrFail($id);
        $user->role_id = $user->roles()->first()?->id;
        $user->role = $user->getRoleNames()->first() ?? '-';
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'primary_contact' => 'nullable|string|max:255',
            'email'           => 'required|email|unique:users,email,' . $id,
            'phone'           => 'required|string|max:20',
            'address'         => 'nullable|string|max:500',
            'password'        => 'nullable|string|min:6',
            'role_id'         => 'required|exists:roles,id',
        ]);

        $user = User::where('user_type', 1)->findOrFail($id);
        $user->name            = $request->name;
        $user->primary_contact = $request->primary_contact;
        $user->email           = $request->email;
        $user->phone           = $request->phone;
        $user->address         = $request->address;
        if ($request->password) $user->password = Hash::make($request->password);
        $user->save();

        $role = Role::find($request->role_id);
        $user->syncRoles($role);

        return response()->json(['message' => 'Employee updated successfully.', 'employee' => $user]);
    }

    public function destroy($id)
    {
        $user = User::where('user_type', 1)->findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Employee deleted successfully.']);
    }

    public function toggleStatus(Request $request)
    {
        $user = User::where('user_type', 1)->findOrFail($request->id);
        $user->status = $request->status;
        $user->save();
        return response()->json(['message' => 'Status updated successfully.']);
    }
}