<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::where('user_type', 1)->orderByDesc('id')->get(['id','name','email','phone','status','primary_contact']);
        return response()->json($employees);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'primary_contact' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'additional_info' => 'nullable|string',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'primary_contact' => $request->primary_contact,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'additional_info' => $request->additional_info,
            'password' => Hash::make($request->password),
            'user_type' => 1,
            'status' => 1,
        ]);

        $role = Role::find($request->role_id);
        $user->syncRoles($role);

        return response()->json(['message' => 'Employee created successfully.', 'employee' => $user]);
    }

    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        $user->role_id = $user->roles()->first()?->id;
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'primary_contact' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'additional_info' => 'nullable|string',
            'password' => 'nullable|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::findOrFail($id);
        $user->name = $request->name;
        $user->primary_contact = $request->primary_contact;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->address = $request->address;
        $user->additional_info = $request->additional_info;
        if ($request->password) $user->password = Hash::make($request->password);
        $user->save();

        $role = Role::find($request->role_id);
        $user->syncRoles($role);

        return response()->json(['message' => 'Employee updated successfully.', 'employee' => $user]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Employee deleted successfully.']);
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->status = $request->status;
        $user->save();
        return response()->json(['message' => 'Status updated successfully.', 'status' => $user->status]);
    }
}