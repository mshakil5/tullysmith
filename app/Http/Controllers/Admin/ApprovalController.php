<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Document;
use App\Models\ServiceJobChecklist;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $status = $request->get('status');

            $notes = Note::with(['job.client', 'user'])
                ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
                ->latest()
                ->get()
                ->map(fn($n) => [
                    'id'         => $n->id,
                    'type'       => 'note',
                    'title'      => $n->note,
                    'job'        => $n->job->job_title ?? '',
                    'client'     => $n->job->client->name ?? '',
                    'created_by' => $n->user->name ?? '',
                    'created_at' => $n->created_at->format('M d, H:i'),
                    'status'     => $n->status,
                ]);

            $documents = Document::with(['job.client', 'user'])
                ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
                ->latest()
                ->get()
                ->map(fn($d) => [
                    'id'         => $d->id,
                    'type'       => 'document',
                    'title'      => $d->title ?? $d->type,
                    'job'        => $d->job->job_title ?? '',
                    'client'     => $d->job->client->name ?? '',
                    'created_by' => $d->user->name ?? '',
                    'created_at' => $d->created_at->format('M d, H:i'),
                    'status'     => $d->status,
                ]);

            $checklists = ServiceJobChecklist::with(['serviceJob.client', 'checklist', 'assignedBy'])
                ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
                ->latest()
                ->get()
                ->map(fn($c) => [
                    'id'         => $c->id,
                    'type'       => 'checklist',
                    'title'      => $c->checklist->title ?? '',
                    'job'        => $c->serviceJob->job_title ?? '',
                    'client'     => $c->serviceJob->client->name ?? '',
                    'created_by' => $c->assignedBy->name ?? '',
                    'created_at' => $c->created_at->format('M d, H:i'),
                    'status'     => $c->status,
                ]);

            $items = $notes->concat($documents)->concat($checklists)->sortByDesc('created_at')->values();

            $pendingCount = Note::where('status', 'pending')->count()
                + Document::where('status', 'pending')->count()
                + ServiceJobChecklist::where('status', 'pending')->count();

            return response()->json([
                'items'         => $items,
                'pending_count' => $pendingCount,
            ]);
        }

        $pendingCount = Note::where('status', 'pending')->count()
            + Document::where('status', 'pending')->count()
            + ServiceJobChecklist::where('status', 'pending')->count();

        return view('admin.approvals.index', compact('pendingCount'));
    }

    public function show($type, $id)
    {
        if ($type === 'note') {
            $item = Note::with(['job.client', 'user'])->findOrFail($id);
        } elseif ($type === 'document') {
            $item = Document::with(['job.client', 'user'])->findOrFail($id);
        } else {
            $item = ServiceJobChecklist::with(['serviceJob.client', 'checklist.items', 'assignedBy'])->findOrFail($id);
        }

        return view('admin.partials.approval-detail', compact('item', 'type'));
    }

    public function action(Request $request, $type, $id)
    {
        $request->validate(['action' => 'required|in:approved,rejected']);

        if ($type === 'note') {
            $item = Note::findOrFail($id);
        } elseif ($type === 'document') {
            $item = Document::findOrFail($id);
        } else {
            $item = ServiceJobChecklist::findOrFail($id);
        }

        $item->update([
            'status' => $request->action,
            'rejection_reason' => $request->action === 'rejected' ? $request->rejection_reason : null,
        ]);

        return response()->json(['success' => true, 'status' => $request->action]);
    }
}