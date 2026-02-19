<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\ServiceJob;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'service_job_id' => 'required|exists:service_jobs,id',
            'note' => 'required|string',
        ]);

        $note = Note::create([
            'service_job_id' => $request->service_job_id,
            'created_by' => auth()->id(),
            'note' => $request->note,
            'status' => auth()->user()->creation_status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully',
            'note' => [
                'id' => $note->id,
                'note' => $note->note,
                'created_by' => auth()->user()->name,
                'created_at' => $note->created_at->format('M d, H:i'),
            ]
        ]);
    }

    public function getNotes($jobId)
    {
        $job = ServiceJob::findOrFail($jobId);
        $notes = $job->notes()->where('status', 'approved')->with('user:id,name')->get();

        return response()->json([
            'notes' => $notes->map(function ($note) {
                return [
                    'id' => $note->id,
                    'note' => $note->note,
                    'created_by' => $note->user->name ?? 'Unknown',
                    'created_at' => $note->created_at->format('M d, H:i'),
                ];
            })
        ]);
    }

    public function destroy($id)
    {
        $note = Note::findOrFail($id);
        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully'
        ]);
    }
}