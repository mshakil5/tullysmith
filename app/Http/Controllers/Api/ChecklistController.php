<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function index()
    {
        $checklists = Checklist::withCount('items')
            ->orderByDesc('id')
            ->get(['id', 'title', 'description', 'is_active', 'created_at']);

        return response()->json($checklists);
    }

    public function activeList()
    {
        $checklists = Checklist::where('is_active', 1)
            ->with('items')
            ->orderByDesc('id')
            ->get()
            ->map(function ($checklist) {
                return [
                    'id' => $checklist->id,
                    'title' => $checklist->title,
                    'description' => $checklist->description,
                    'is_active' => $checklist->is_active,
                    'items' => $checklist->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'question' => $item->question,
                            'type' => $item->type,
                            'is_required' => $item->is_required,
                        ];
                    })->toArray(),
                ];
            });

        return response()->json(['checklists' => $checklists]);
    }

    public function show($id)
    {
        $checklist = Checklist::with('items')->findOrFail($id);
        return response()->json($checklist);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable',

            'items' => 'required|array|min:1',
            'items.*.question' => 'required|string',
            'items.*.type' => 'required|in:yes_no,yes_no_na,text_input,photo_upload',
            'items.*.is_required' => 'nullable',
        ]);

        $isActive = $request->has('is_active') && $request->input('is_active') != 0;

        $checklist = Checklist::create([
            'title' => $request->title,
            'description' => $request->description,
            'is_active' => $isActive,
        ]);

        foreach ($request->items as $item) {
            $isRequired = isset($item['is_required']) && $item['is_required'] != 0;

            ChecklistItem::create([
                'checklist_id' => $checklist->id,
                'question' => $item['question'],
                'type' => $item['type'],
                'is_required' => $isRequired,
            ]);
        }

        return response()->json([
            'message' => 'Checklist created successfully.',
            'checklist' => $checklist
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable',

            'items' => 'required|array|min:1',
            'items.*.question' => 'required|string',
            'items.*.type' => 'required|in:yes_no,yes_no_na,text_input,photo_upload',
            'items.*.is_required' => 'nullable',
        ]);

        $checklist = Checklist::findOrFail($id);

        $isActive = $request->has('is_active') && $request->input('is_active') != 0;

        $checklist->update([
            'title' => $request->title,
            'description' => $request->description,
            'is_active' => $isActive,
        ]);

        // remove old items
        ChecklistItem::where('checklist_id', $checklist->id)->delete();

        // insert new items
        foreach ($request->items as $item) {
            $isRequired = isset($item['is_required']) && $item['is_required'] != 0;

            ChecklistItem::create([
                'checklist_id' => $checklist->id,
                'question' => $item['question'],
                'type' => $item['type'],
                'is_required' => $isRequired,
            ]);
        }

        return response()->json([
            'message' => 'Checklist updated successfully.',
            'checklist' => $checklist
        ]);
    }

    public function destroy($id)
    {
        $checklist = Checklist::findOrFail($id);
        $checklist->delete();

        return response()->json(['message' => 'Checklist deleted successfully.']);
    }

    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:0,1'
        ]);

        $checklist = Checklist::findOrFail($id);
        $checklist->is_active = (int)$request->status;
        $checklist->save();

        return response()->json([
            'message' => 'Status updated successfully.',
            'status' => $checklist->is_active
        ]);
    }

    public function items($id)
    {
        $checklist = Checklist::findOrFail($id);
        return response()->json(['items' => $checklist->items]);
    }
}