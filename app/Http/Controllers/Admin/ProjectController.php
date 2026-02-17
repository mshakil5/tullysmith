<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use DataTables;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $projects = Project::select(['id', 'name', 'address', 'project_area', 'client_id'])->orderByDesc('id');
            return DataTables::of($projects)
                ->addIndexColumn()
                ->addColumn('client_name', function ($row) {
                    return $row->client->name ?? 'N/A';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown">
                          <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown"><i class="ri-more-fill"></i></button>
                          <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item EditBtn" data-id="' . $row->id . '"><i class="ri-pencil-fill me-2"></i>Edit</button></li>
                            <li class="dropdown-divider"></li>
                            <li><button class="dropdown-item deleteBtn" data-delete-url="' . route('project.delete', $row->id) . '" data-method="DELETE" data-table="#projectTable"><i class="ri-delete-bin-fill me-2"></i>Delete</button></li>
                          </ul>
                        </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $clients = User::where('user_type', 0)->latest()->get();

        return view('admin.project.index', compact('clients'));
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

        Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'project_area' => $request->project_area,
            'client_id' => $request->client_id,
        ]);

        return response()->json(['message' => 'Project created successfully.']);
    }

    public function edit($id)
    {
        $project = Project::findOrFail($id);
        return response()->json($project);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'project_area' => 'nullable|string|max:255',
            'client_id' => 'required|exists:users,id',
        ]);

        $project = Project::findOrFail($request->id);
        $project->update($request->only(['name', 'description', 'address', 'latitude', 'longitude', 'project_area', 'client_id']));

        return response()->json(['message' => 'Project updated successfully.']);
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();
        return response()->json(['message' => 'Project deleted successfully.']);
    }
}