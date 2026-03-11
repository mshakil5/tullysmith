<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = User::select(['id', 'name', 'email', 'phone', 'primary_contact', 'address', 'additional_info', 'status'])
            ->where('user_type', 0)
            ->orderByDesc('id');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $clients = $query->paginate(15);

        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'primary_contact' => 'nullable|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'phone'           => 'required|string|max:20',
            'address'         => 'nullable|string|max:500',
            'additional_info' => 'nullable|string',
        ]);

        $client = User::create([
            'name'            => $request->name,
            'primary_contact' => $request->primary_contact,
            'email'           => $request->email,
            'phone'           => $request->phone,
            'address'         => $request->address,
            'additional_info' => $request->additional_info,
            'password'        => null,
            'user_type'       => 0,
            'status'          => 1,
        ]);

        return response()->json(['message' => 'Client created successfully.', 'client' => $client], 201);
    }

    public function show($id)
    {
        $client = User::where('user_type', 0)->findOrFail($id);
        return response()->json($client);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'primary_contact' => 'nullable|string|max:255',
            'email'           => 'required|email|unique:users,email,' . $id,
            'phone'           => 'required|string|max:20',
            'address'         => 'nullable|string|max:500',
            'additional_info' => 'nullable|string',
        ]);

        $client = User::where('user_type', 0)->findOrFail($id);
        $client->update($request->only(['name', 'primary_contact', 'email', 'phone', 'address', 'additional_info']));

        return response()->json(['message' => 'Client updated successfully.', 'client' => $client]);
    }

    public function destroy($id)
    {
        $client = User::where('user_type', 0)->findOrFail($id);
        $client->delete();
        return response()->json(['message' => 'Client deleted successfully.']);
    }

    public function toggleStatus(Request $request)
    {
        $client = User::where('user_type', 0)->findOrFail($request->id);
        $client->status = $request->status;
        $client->save();
        return response()->json(['message' => 'Status updated successfully.']);
    }
}