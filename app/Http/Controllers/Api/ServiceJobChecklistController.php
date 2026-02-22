<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceJobChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceJobChecklistController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'service_job_id' => 'required|exists:service_jobs,id',
            'checklist_id' => 'required|exists:checklists,id',
        ]);

        ServiceJobChecklist::create([
            'service_job_id' => $request->service_job_id,
            'checklist_id' => $request->checklist_id,
            'status' => auth()->user()->creation_status,
            'assigned_by' => Auth::id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Checklist assigned successfully']);
    }

    public function getChecklists($jobId)
    {
        $checklists = ServiceJobChecklist::where('service_job_id', $jobId)
            ->with('checklist.items')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->checklist->title ?? '',
                    'description' => $item->checklist->description,
                    'items' => $item->checklist->items->map(function ($checklistItem) {
                        return [
                            'id' => $checklistItem->id,
                            'question' => $checklistItem->question,
                            'type' => $checklistItem->type,
                            'is_required' => $checklistItem->is_required,
                        ];
                    })->toArray(),
                ];
            });

        return response()->json(['checklists' => $checklists]);
    }

    public function destroy($id)
    {
        $assignment = ServiceJobChecklist::findOrFail($id);
        $assignment->delete();

        return response()->json(['success' => true, 'message' => 'Checklist removed successfully']);
    }
}