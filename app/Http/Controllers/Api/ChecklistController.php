<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function index(Request $request)
    {
        $query = Checklist::select(['id', 'title', 'description', 'is_active'])
            ->withCount('items')
            ->orderByDesc('id');

        if ($request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $checklists = $query->paginate(15);

        return response()->json([
            'data'      => $checklists->items(),
            'last_page' => $checklists->lastPage(),
            'total'     => $checklists->total(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            'is_active'              => 'nullable|boolean',
            'items'                  => 'required|array|min:1',
            'items.*.question'       => 'required|string',
            'items.*.type'           => 'required|in:yes_no,yes_no_na,text_input,photo_upload',
            'items.*.is_required'    => 'nullable|boolean',
        ]);

        $checklist = Checklist::create([
            'title'       => $request->title,
            'description' => $request->description,
            'is_active'   => $request->is_active ?? true,
        ]);

        foreach ($request->items as $item) {
            if (!empty($item['question'])) {
                ChecklistItem::create([
                    'checklist_id' => $checklist->id,
                    'question'     => $item['question'],
                    'type'         => $item['type'],
                    'is_required'  => $item['is_required'] ?? false,
                ]);
            }
        }

        return response()->json(['message' => 'Checklist created successfully.', 'checklist' => $checklist->load('items')], 201);
    }

    public function show($id)
    {
        $checklist = Checklist::with('items')->findOrFail($id);
        return response()->json($checklist);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            'is_active'              => 'nullable|boolean',
            'items'                  => 'required|array|min:1',
            'items.*.question'       => 'required|string',
            'items.*.type'           => 'required|in:yes_no,yes_no_na,text_input,photo_upload',
            'items.*.is_required'    => 'nullable|boolean',
        ]);

        $checklist = Checklist::findOrFail($id);
        $checklist->update([
            'title'       => $request->title,
            'description' => $request->description,
            'is_active'   => $request->is_active ?? $checklist->is_active,
        ]);

        ChecklistItem::where('checklist_id', $checklist->id)->delete();

        foreach ($request->items as $item) {
            if (!empty($item['question'])) {
                ChecklistItem::create([
                    'checklist_id' => $checklist->id,
                    'question'     => $item['question'],
                    'type'         => $item['type'],
                    'is_required'  => $item['is_required'] ?? false,
                ]);
            }
        }

        return response()->json(['message' => 'Checklist updated successfully.', 'checklist' => $checklist->load('items')]);
    }

    public function destroy($id)
    {
        $checklist = Checklist::findOrFail($id);
        $checklist->delete();
        return response()->json(['message' => 'Checklist deleted successfully.']);
    }

    public function toggleStatus(Request $request)
    {
        $checklist = Checklist::findOrFail($request->id);
        $checklist->is_active = $request->status;
        $checklist->save();
        return response()->json(['message' => 'Status updated successfully.']);
    }
}