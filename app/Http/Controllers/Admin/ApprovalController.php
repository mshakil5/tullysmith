<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceJob;
use App\Models\ServiceJobChecklist;
use App\Models\TimeLog;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $status = $request->get('status');

            $checklists = ServiceJobChecklist::with(['serviceJob:id,job_title', 'checklist:id,title', 'assignedBy:id,name'])
                ->whereHas('answers')
                ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
                ->latest()
                ->get(['id', 'service_job_id', 'checklist_id', 'assigned_by', 'status', 'created_at'])
                ->map(fn($c) => [
                    'id'         => $c->id,
                    'type'       => 'checklist',
                    'title'      => $c->checklist->title ?? '',
                    'job'        => $c->serviceJob->job_title ?? '',
                    'created_by' => $c->assignedBy->name ?? '',
                    'created_at' => $c->created_at->format('M d, H:i'),
                    'status'     => $c->status,
                ]);

            $timelogs = TimeLog::with(['worker:id,name', 'job:id,job_title'])
                ->whereNotNull('clock_out_at')
                ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
                ->latest()
                ->get(['id', 'worker_id', 'service_job_id', 'status', 'clock_in_at'])
                ->map(fn($t) => [
                    'id'         => $t->id,
                    'type'       => 'timelog',
                    'title'      => $t->job->job_title ?? '—',
                    'job'        => $t->job->job_title ?? '',
                    'created_by' => $t->worker->name ?? '',
                    'created_at' => $t->clock_in_at->format('M d, H:i'),
                    'status'     => $t->status,
                ]);

            $sjStatus = $status;
            if ($status === 'pending')  $sjStatus = 'completed';
            if ($status === 'approved') $sjStatus = 'confirmed';
            if ($status === 'rejected') $sjStatus = null;

            $serviceJobs = ServiceJob::with(['client:id,name'])
                ->when($sjStatus, fn($q) => $q->where('status', $sjStatus))
                ->when(!$sjStatus && $status === 'all', fn($q) => $q->whereIn('status', ['completed', 'confirmed']))
                ->when(!$sjStatus && $status !== 'all', fn($q) => $q->whereRaw('1=0'))
                ->latest()
                ->get(['id', 'job_id', 'job_title', 'client_id', 'status', 'start_date', 'end_date', 'created_at'])
                ->map(fn($j) => [
                    'id'         => $j->id,
                    'type'       => 'servicejob',
                    'title'      => $j->job_title ?? '',
                    'job'        => $j->job_id ?? '',
                    'created_by' => $j->client->name ?? '',
                    'created_at' => $j->created_at->format('M d, H:i'),
                    'status'     => $j->status === 'completed' ? 'pending' : ($j->status === 'confirmed' ? 'approved' : $j->status),
                ]);

            $items = $checklists->concat($timelogs)->concat($serviceJobs)
                ->sortByDesc('created_at')->values();

            $pendingCount  = ServiceJobChecklist::whereHas('answers')->where('status', 'pending')->count()
                + TimeLog::whereNotNull('clock_out_at')->where('status', 'pending')->count()
                + ServiceJob::where('status', 'completed')->count();

            $approvedCount = ServiceJobChecklist::whereHas('answers')->where('status', 'approved')->count()
                + TimeLog::whereNotNull('clock_out_at')->where('status', 'approved')->count()
                + ServiceJob::where('status', 'confirmed')->count();

            $rejectedCount = ServiceJobChecklist::whereHas('answers')->where('status', 'rejected')->count()
                + TimeLog::whereNotNull('clock_out_at')->where('status', 'rejected')->count();

            return response()->json([
                'items'          => $items,
                'pending_count'  => $pendingCount,
                'approved_count' => $approvedCount,
                'rejected_count' => $rejectedCount,
                'all_count'      => $pendingCount + $approvedCount + $rejectedCount,
            ]);
        }

        return view('admin.approvals.index');
    }

    public function show($type, $id)
    {
        if ($type === 'timelog') {
            $item = TimeLog::with(['worker:id,name', 'job:id,job_title', 'assignment'])->findOrFail($id);
        } elseif ($type === 'servicejob') {
            $item = ServiceJob::with(['client:id,name'])->findOrFail($id);
        } else {
            $item = ServiceJobChecklist::with([
                'serviceJob:id,job_title',
                'checklist:id,title',
                'assignedBy:id,name',
                'answers.item',
                'answers.answeredBy:id,name',
            ])->findOrFail($id);
        }

        return view('admin.partials.approval-detail', compact('item', 'type'));
    }

    public function action(Request $request, $type, $id)
    {
        $request->validate(['action' => 'required|in:approved,rejected']);

        if ($type === 'servicejob') {
            $item = ServiceJob::findOrFail($id);
            $item->update([
                'status' => $request->action === 'approved' ? 'confirmed' : 'active',
            ]);
        } elseif ($type === 'timelog') {
            $item = TimeLog::findOrFail($id);
            $item->update([
                'status'           => $request->action,
                'rejection_reason' => $request->action === 'rejected' ? $request->rejection_reason : null,
            ]);
        } else {
            $item = ServiceJobChecklist::findOrFail($id);
            $item->update([
                'status'           => $request->action,
                'rejection_reason' => $request->action === 'rejected' ? $request->rejection_reason : null,
            ]);
        }

        return response()->json(['success' => true, 'status' => $request->action]);
    }
}