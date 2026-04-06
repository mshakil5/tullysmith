<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ServiceJob;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::with('job:id,job_title,job_id')->latest();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        $paginated = $query->paginate(15);

        $jobs = ServiceJob::whereIn('status', ['active', 'pending'])
            ->select('id', 'job_title', 'job_id')
            ->latest()
            ->get();

        return response()->json([
            ...$paginated->toArray(),
            'jobs' => $jobs,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'required|string',
            'priority'       => 'required|in:low,medium,high',
            'service_job_id' => 'nullable|exists:service_jobs,id',
            'expires_at'     => 'nullable|date',
        ]);

        $announcement = Announcement::create($request->only('title', 'content', 'priority', 'service_job_id', 'expires_at'));

        return response()->json(['message' => 'Announcement created successfully.', 'announcement' => $announcement], 201);
    }

    public function show($id)
    {
        return response()->json(Announcement::with('job:id,job_title,job_id')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'required|string',
            'priority'       => 'required|in:low,medium,high',
            'service_job_id' => 'nullable|exists:service_jobs,id',
            'expires_at'     => 'nullable|date',
        ]);

        $announcement = Announcement::findOrFail($id);
        $announcement->update($request->only('title', 'content', 'priority', 'service_job_id', 'expires_at'));

        return response()->json(['message' => 'Announcement updated successfully.', 'announcement' => $announcement]);
    }

    public function destroy($id)
    {
        Announcement::findOrFail($id)->delete();
        return response()->json(['message' => 'Announcement deleted successfully.']);
    }

    public function toggleStatus(Request $request)
    {
        $announcement = Announcement::findOrFail($request->id);
        $announcement->status = $request->status;
        $announcement->save();
        return response()->json(['message' => 'Status updated successfully.']);
    }
}