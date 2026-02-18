<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('client:id,name')
            ->orderByDesc('id')
            ->get(['id','name','address','project_area','client_id','latitude','longitude','description']);

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'project_area' => 'nullable|string|max:255',
            'client_id' => 'required|exists:users,id',
        ]);

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'project_area' => $request->project_area,
            'client_id' => $request->client_id,
        ]);

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => $project
        ]);
    }

    public function show($id)
    {
        $project = Project::with('client:id,name')->findOrFail($id);
        return response()->json($project);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'project_area' => 'nullable|string|max:255',
            'client_id' => 'required|exists:users,id',
        ]);

        $project = Project::findOrFail($id);

        $project->update([
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'project_area' => $request->project_area,
            'client_id' => $request->client_id,
        ]);

        return response()->json([
            'message' => 'Project updated successfully.',
            'project' => $project
        ]);
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.'
        ]);
    }
}