<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceJob;
use App\Models\ServiceJobChecklist;
use App\Models\TimeLog;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');
        $perPage = 15;

        $checklists = ServiceJobChecklist::with(['serviceJob:id,job_title', 'checklist:id,title', 'assignedBy:id,name'])
            ->whereHas('answers')
            ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage);

        $timelogs = TimeLog::with(['worker:id,name', 'job:id,job_title'])
            ->whereNotNull('clock_out_at')
            ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage);

        $sjStatus = $status;
        if ($status === 'pending')  $sjStatus = 'completed';
        if ($status === 'approved') $sjStatus = 'confirmed';
        if ($status === 'rejected') $sjStatus = null;

        $serviceJobs = ServiceJob::with(['client:id,name'])
            ->when($sjStatus, fn($q) => $q->where('status', $sjStatus))
            ->when(!$sjStatus && $status === 'all', fn($q) => $q->whereIn('status', ['completed', 'confirmed']))
            ->when(!$sjStatus && $status !== 'all', fn($q) => $q->whereRaw('1=0'))
            ->latest()
            ->paginate($perPage);

        $mappedChecklists = collect($checklists->items())->map(fn($c) => [
            'id'         => $c->id,
            'type'       => 'checklist',
            'title'      => $c->checklist->title ?? '',
            'job'        => $c->serviceJob->job_title ?? '',
            'created_by' => $c->assignedBy->name ?? '',
            'created_at' => $c->created_at->format('M d, H:i'),
            'status'     => $c->status,
        ]);

        $mappedTimelogs = collect($timelogs->items())->map(fn($t) => [
            'id'         => $t->id,
            'type'       => 'timelog',
            'title'      => $t->job->job_title ?? '—',
            'job'        => $t->job->job_title ?? '',
            'created_by' => $t->worker->name ?? '',
            'created_at' => $t->clock_in_at->format('M d, H:i'),
            'status'     => $t->status,
        ]);

        $mappedServiceJobs = collect($serviceJobs->items())->map(fn($j) => [
            'id'         => $j->id,
            'type'       => 'servicejob',
            'title'      => $j->job_title ?? '',
            'job'        => $j->job_id ?? '',
            'created_by' => $j->client->name ?? '',
            'created_at' => $j->created_at->format('M d, H:i'),
            'status'     => $j->status === 'completed' ? 'pending' : ($j->status === 'confirmed' ? 'approved' : $j->status),
        ]);

        $items = $mappedChecklists->concat($mappedTimelogs)->concat($mappedServiceJobs)
            ->sortByDesc('created_at')->values();

        $lastPage = max($checklists->lastPage(), $timelogs->lastPage(), $serviceJobs->lastPage());

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
            'last_page'      => $lastPage,
            'pending_count'  => $pendingCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
            'all_count'      => $pendingCount + $approvedCount + $rejectedCount,
        ]);
    }

    public function show($type, $id)
    {
        if ($type === 'timelog') {
            $item = TimeLog::with(['worker:id,name', 'job:id,job_title', 'assignment'])->findOrFail($id);

            return response()->json([
                'id'               => $item->id,
                'type'             => 'timelog',
                'title'            => $item->job->job_title ?? '—',
                'date'             => $item->assignment ? $item->assignment->formatted_date : $item->clock_in_at->format('d F Y'),
                'submitted_by'     => $item->worker->name ?? '',
                'status'           => $item->status,
                'rejection_reason' => $item->rejection_reason,
                'clock_in_time'    => $item->clock_in_at->format('h:i A'),
                'clock_in_date'    => $item->clock_in_at->format('M d, Y'),
                'clock_out_time'   => $item->clock_out_at?->format('h:i A'),
                'clock_out_date'   => $item->clock_out_at?->format('M d, Y'),
                'total_hours'      => $item->total_hours ? number_format($item->total_hours, 2) : null,
                'clock_in_photo'  => $item->clock_in_photo ? asset($item->clock_in_photo) : null,
                'clock_out_photo' => $item->clock_out_photo ? asset($item->clock_out_photo) : null,
            ]);

        } elseif ($type === 'servicejob') {
            $item = ServiceJob::with(['client:id,name'])->findOrFail($id);

            return response()->json([
                'id'               => $item->id,
                'type'             => 'servicejob',
                'title'            => $item->job_title ?? '—',
                'job_id'           => $item->job_id ?? '',
                'submitted_by'     => $item->client->name ?? '',
                'status'           => $item->status === 'completed' ? 'pending' : ($item->status === 'confirmed' ? 'approved' : $item->status),
                'rejection_reason' => null,
                'priority'         => $item->priority,
                'estimated_hours'  => $item->estimated_hours ?? 0,
                'start_date'       => $item->formattedStartDate(),
                'end_date'         => $item->formattedEndDate(),
            ]);

        } else {
            $item = ServiceJobChecklist::with([
                'serviceJob:id,job_title',
                'checklist:id,title',
                'checklist.items',
                'assignedBy:id,name',
                'answers.item',
                'answers.answeredBy:id,name',
            ])->findOrFail($id);

            $checklistItems = $item->checklist->items->map(function ($checkItem) use ($item) {
                $answer = $item->answers->firstWhere('checklist_item_id', $checkItem->id);
                return [
                    'id'          => $checkItem->id,
                    'question'    => $checkItem->question,
                    'type'        => $checkItem->type,
                    'answer'      => $answer?->answer,
                    'photo_path'  => $answer?->photo_path ? asset($answer->photo_path) : null,
                    'answered_by' => $answer?->answeredBy?->name ?? '',
                ];
            });

            return response()->json([
                'id'               => $item->id,
                'type'             => 'checklist',
                'title'            => $item->checklist->title ?? '',
                'job'              => $item->serviceJob->job_title ?? '',
                'submitted_by'     => $item->assignedBy->name ?? '',
                'status'           => $item->status,
                'rejection_reason' => $item->rejection_reason,
                'items'            => $checklistItems,
            ]);
        }
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