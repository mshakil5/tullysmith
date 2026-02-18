<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class ClientController extends Controller
{
    public function index()
    {
        $clients = User::where('user_type', 0)
            ->orderByDesc('id')
            ->get(['id','name','email','phone','status','primary_contact']);

        return response()->json($clients);
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
        ]);

        $client = User::create([
            'name' => $request->name,
            'primary_contact' => $request->primary_contact,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'additional_info' => $request->additional_info,
            'password' => null,
            'user_type' => 0,
            'status' => 1,
        ]);

        return response()->json([
            'message' => 'Client created successfully.',
            'client' => $client
        ]);
    }

    public function show($id)
    {
        $client = User::where('user_type', 0)->findOrFail($id);
        return response()->json($client);
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
        ]);

        $client = User::where('user_type', 0)->findOrFail($id);

        $client->name = $request->name;
        $client->primary_contact = $request->primary_contact;
        $client->email = $request->email;
        $client->phone = $request->phone;
        $client->address = $request->address;
        $client->additional_info = $request->additional_info;
        $client->save();

        return response()->json([
            'message' => 'Client updated successfully.',
            'client' => $client
        ]);
    }

    public function destroy($id)
    {
        $client = User::where('user_type', 0)->findOrFail($id);
        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully.'
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:0,1'
        ]);

        $client = User::where('user_type', 0)->findOrFail($id);
        $client->status = (int)$request->status;
        $client->save();

        return response()->json([
            'message' => 'Status updated successfully.',
            'status' => $client->status
        ]);
    }
}