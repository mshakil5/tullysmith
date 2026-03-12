<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Checklist;
use App\Models\ChecklistAnswer;
use App\Models\ChecklistItem;
use App\Models\ServiceJobChecklist;
use DataTables;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class ChecklistController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $checklists = Checklist::select(['id', 'title', 'description', 'is_active', 'created_at'])->orderByDesc('id');
            
            return DataTables::of($checklists)
                ->addIndexColumn()
                ->addColumn('items_count', function ($row) {
                    return $row->items()->count();
                })
                ->addColumn('is_active', function ($row) {
                    $checked = $row->is_active ? 'checked' : '';
                    return '<div class="form-check form-switch">
                                <input class="form-check-input toggle-status" data-id="' . $row->id . '" type="checkbox" ' . $checked . '>
                            </div>';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown">
                          <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown"><i class="ri-more-fill"></i></button>
                          <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item EditBtn" data-id="' . $row->id . '"><i class="ri-pencil-fill me-2"></i>Edit</button></li>
                            <li class="dropdown-divider"></li>
                            <li><button class="dropdown-item deleteBtn" data-delete-url="' . route('checklist.delete', $row->id) . '" data-method="DELETE" data-table="#checklistTable"><i class="ri-delete-bin-fill me-2"></i>Delete</button></li>
                          </ul>
                        </div>';
                })
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        return view('admin.checklist.index');
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

        $isActive = $request->has('is_active') && $request->input('is_active') !== 'off';

        $checklist = Checklist::create([
            'title' => $request->title,
            'description' => $request->description,
            'is_active' => $isActive,
        ]);

        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                if (!empty($item['question'])) {
                    $isRequired = isset($item['is_required']) && $item['is_required'] !== 'off';
                    ChecklistItem::create([
                        'checklist_id' => $checklist->id,
                        'question' => $item['question'],
                        'type' => $item['type'],
                        'is_required' => $isRequired,
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Checklist created successfully.']);
    }

    public function edit($id)
    {
        $checklist = Checklist::with('items')->findOrFail($id);
        return response()->json($checklist);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:checklists,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
            'items' => 'required|array|min:1',
            'items.*.question' => 'required|string',
            'items.*.type' => 'required|in:yes_no,yes_no_na,text_input,photo_upload',
            'items.*.is_required' => 'nullable',
        ]);

        $checklist = Checklist::findOrFail($request->id);
        
        $isActive = $request->has('is_active') && $request->input('is_active') !== 'off';

        $checklist->update([
            'title' => $request->title,
            'description' => $request->description,
            'is_active' => $isActive,
        ]);

        ChecklistItem::where('checklist_id', $checklist->id)->delete();

        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                if (!empty($item['question'])) {
                    $isRequired = isset($item['is_required']) && $item['is_required'] !== 'off';
                    ChecklistItem::create([
                        'checklist_id' => $checklist->id,
                        'question' => $item['question'],
                        'type' => $item['type'],
                        'is_required' => $isRequired,
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Checklist updated successfully.']);
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
        $checklist->is_active = (bool) $request->status;
        $checklist->save();
        return response()->json(['message' => 'Status updated successfully.']);
    }

    public function getActiveList()
    {
        $checklists = Checklist::where('is_active', 1)
            ->with('items')
            ->get()
            ->map(function ($checklist) {
                return [
                    'id' => $checklist->id,
                    'title' => $checklist->title,
                    'description' => $checklist->description,
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

    public function getItems($checklistId)
    {
        $checklist = Checklist::findOrFail($checklistId);
        $items = $checklist->items;

        return response()->json(['items' => $items]);
    }

    public function checklists($jobId)
    {
        $checklists = ServiceJobChecklist::where('service_job_id', $jobId)
            ->with(['checklist.items', 'answers.answeredBy'])
            ->get();

        if ($checklists->isEmpty()) {
            return '<p class="text-muted text-center py-4">No checklists assigned yet</p>';
        }

        return view('admin.checklist.checklist-answers', compact('checklists'))->render();
    }

    public function saveAnswers(Request $request, $id)
    {
        $assignment = ServiceJobChecklist::findOrFail($id);
        $answers    = $request->input('answers', []);
        $photos     = $request->file('photos', []);

        foreach ($answers as $itemId => $answer) {
            ChecklistAnswer::updateOrCreate(
                [
                    'service_job_checklist_id' => $assignment->id,
                    'checklist_item_id'        => $itemId,
                ],
                [
                    'answer'      => $answer,
                    'answered_by' => auth()->id(),
                ]
            );
        }

        foreach ($photos as $itemId => $file) {
            $filename = 'checklist_' . $id . '_' . $itemId . '_' . mt_rand(10000000, 99999999) . '.webp';
            $path     = public_path('uploads/checklist-answers/');

            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            Image::make($file)->encode('webp', 75)->save($path . $filename);

            $photoPath = '/uploads/checklist-answers/' . $filename;

            $existing = ChecklistAnswer::where('service_job_checklist_id', $assignment->id)
                ->where('checklist_item_id', $itemId)
                ->first();

            if ($existing && $existing->photo_path) {
                $oldFile = public_path($existing->photo_path);
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            ChecklistAnswer::updateOrCreate(
                [
                    'service_job_checklist_id' => $assignment->id,
                    'checklist_item_id'        => $itemId,
                ],
                [
                    'answer'      => $photoPath,
                    'photo_path'  => $photoPath,
                    'answered_by' => auth()->id(),
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'Answers saved.']);
    }
}