<?php

namespace App\Http\Controllers\Admin;

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
            'checklist_id'   => 'required|exists:checklists,id',
            'show_at'        => 'required|in:clock_in,clock_out,both',
        ]);

        if ($request->show_at === 'both') {
            ServiceJobChecklist::create([
                'service_job_id' => $request->service_job_id,
                'checklist_id'   => $request->checklist_id,
                'status'         => 'pending',
                'show_at'        => 'clock_in',
                'assigned_by'    => Auth::id(),
            ]);
            ServiceJobChecklist::create([
                'service_job_id' => $request->service_job_id,
                'checklist_id'   => $request->checklist_id,
                'status'         => 'pending',
                'show_at'        => 'clock_out',
                'assigned_by'    => Auth::id(),
            ]);
        } else {
            ServiceJobChecklist::create([
                'service_job_id' => $request->service_job_id,
                'checklist_id'   => $request->checklist_id,
                'status'         => 'pending',
                'show_at'        => $request->show_at,
                'assigned_by'    => Auth::id(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Checklist assigned successfully']);
    }

    public function destroy($id)
    {
        $assignment = ServiceJobChecklist::with('answers')->findOrFail($id);

        foreach ($assignment->answers as $answer) {
            if ($answer->photo_path && file_exists(public_path($answer->photo_path))) {
                unlink(public_path($answer->photo_path));
            }
            $answer->delete();
        }

        $assignment->delete();

        return response()->json(['success' => true, 'message' => 'Checklist removed successfully']);
    }
}