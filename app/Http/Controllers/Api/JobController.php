<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checklist;
use App\Models\ChecklistAnswer;
use App\Models\Document;
use App\Models\Note;
use App\Models\ServiceJob;
use App\Models\ServiceJobChecklist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceJob::with('client:id,name')
            ->select([
                'id',
                'job_id',
                'job_title',
                'client_id',
                'address_line1',
                'address_line2',
                'city',
                'postcode',
                'status',
                'priority',
                'start_date',
                'end_date',
                'estimated_hours',
                'created_at'
            ])
            ->where('status', '!=', 'archived')
            ->orderByDesc('id');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('job_title', 'like', "%{$request->search}%")
                    ->orWhere('job_id', 'like', "%{$request->search}%")
                    ->orWhere('postcode', 'like', "%{$request->search}%")
                    ->orWhereHas(
                        'client',
                        fn($cq) =>
                        $cq->where('name', 'like', "%{$request->search}%")
                    );
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $jobs = $query->paginate(15);

        $jobs->getCollection()->transform(function ($job) {
            $job->client_name    = $job->client->name ?? '-';
            $job->total_expenses = $job->totalExpenses();
            $job->expenses_count = $job->documents()
                ->whereIn('type', ['invoice', 'receipt'])
                ->where('status', 'approved')
                ->count();
            return $job;
        });

        $clients = User::where('user_type', 0)->select('id', 'name')->latest()->get();

        return response()->json([
            'data'      => $jobs->items(),
            'last_page' => $jobs->lastPage(),
            'total'     => $jobs->total(),
            'clients'   => $clients,
        ]);
    }

    public function archived(Request $request)
    {
        $query = ServiceJob::with('client:id,name')
            ->select([
                'id',
                'job_id',
                'job_title',
                'client_id',
                'address_line1',
                'address_line2',
                'city',
                'postcode',
                'status',
                'priority',
                'start_date',
                'end_date',
                'estimated_hours',
                'created_at'
            ])
            ->where('status', 'archived')
            ->orderByDesc('id');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('job_title', 'like', "%{$request->search}%")
                    ->orWhere('job_id', 'like', "%{$request->search}%")
                    ->orWhere('postcode', 'like', "%{$request->search}%")
                    ->orWhereHas(
                        'client',
                        fn($cq) =>
                        $cq->where('name', 'like', "%{$request->search}%")
                    );
            });
        }

        $jobs = $query->paginate(15);

        $jobs->getCollection()->transform(function ($job) {
            $job->client_name    = $job->client->name ?? '-';
            $job->total_expenses = $job->totalExpenses();
            $job->expenses_count = $job->documents()
                ->whereIn('type', ['invoice', 'receipt'])
                ->where('status', 'approved')
                ->count();
            return $job;
        });

        return response()->json([
            'data'      => $jobs->items(),
            'last_page' => $jobs->lastPage(),
            'total'     => $jobs->total(),
        ]);
    }

    public function nextJobId()
    {
        $last = ServiceJob::orderByRaw("CAST(SUBSTRING(job_id, 5) AS UNSIGNED) DESC")->first();
        $nextNum = $last ? ((int) substr($last->job_id, 4) + 1) : 1;
        return response()->json(['next_job_id' => 'JOB-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT)]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_id'          => 'required|string|max:20|unique:service_jobs,job_id',
            'job_title'       => 'required|string|max:255',
            'client_id'       => 'required|integer|exists:users,id',
            'description'     => 'nullable|string',
            'instructions'    => 'nullable|string',
            'status'          => 'required|string|max:50',
            'priority'        => 'required|string|max:50',
            'address_line1'   => 'nullable|string|max:255',
            'address_line2'   => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:100',
            'postcode'        => 'required|string|max:20',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date',
            'estimated_hours' => 'nullable|numeric',
        ]);

        $job = ServiceJob::create([
            'job_id'          => $request->job_id,
            'job_title'       => $request->job_title,
            'client_id'       => $request->client_id,
            'description'     => $request->description,
            'instructions'    => $request->instructions,
            'status'          => $request->status,
            'priority'        => $request->priority,
            'address_line1'   => $request->address_line1,
            'address_line2'   => $request->address_line2,
            'city'            => $request->city,
            'postcode'        => $request->postcode,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'estimated_hours' => $request->estimated_hours,
        ]);

        return response()->json(['message' => 'Job created successfully.', 'job' => $job], 201);
    }

    public function show($id)
    {
        $job = ServiceJob::with('client:id,name')->findOrFail($id);
        $job->client_name = $job->client->name ?? '-';
        return response()->json($job);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'job_title'       => 'required|string|max:255',
            'client_id'       => 'required|integer|exists:users,id',
            'description'     => 'nullable|string',
            'instructions'    => 'nullable|string',
            'status'          => 'required|string|max:50',
            'priority'        => 'required|string|max:50',
            'address_line1'   => 'nullable|string|max:255',
            'address_line2'   => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:100',
            'postcode'        => 'required|string|max:20',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date',
            'estimated_hours' => 'nullable|numeric',
        ]);

        $job = ServiceJob::findOrFail($id);
        $job->update([
            'job_title'       => $request->job_title,
            'client_id'       => $request->client_id,
            'description'     => $request->description,
            'instructions'    => $request->instructions,
            'status'          => $request->status,
            'priority'        => $request->priority,
            'address_line1'   => $request->address_line1,
            'address_line2'   => $request->address_line2,
            'city'            => $request->city,
            'postcode'        => $request->postcode,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'estimated_hours' => $request->estimated_hours,
        ]);

        return response()->json(['message' => 'Job updated successfully.', 'job' => $job]);
    }

    public function destroy($id)
    {
        $job = ServiceJob::findOrFail($id);

        if ($job->assignments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete. Job already assigned to workers.'
            ], 422);
        }

        $job->delete();

        return response()->json([
            'message' => 'Job deleted successfully.'
        ]);
    }

    public function getAllExpenses()
    {
        $query = Document::with(['job:id,job_id,job_title', 'user:id,name'])
            ->whereIn('type', ['invoice', 'receipt'])
            ->where('status', 'approved')
            ->select(['id', 'service_job_id', 'created_by', 'type', 'title', 'amount', 'invoice_date', 'file', 'created_at']);

        if (request()->has('job_id')) {
            $query->where('service_job_id', request('job_id'));
        }

        if (request()->has('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhereHas('job', fn($jq) => $jq->where('job_title', 'like', "%{$search}%")
                        ->orWhere('job_id', 'like', "%{$search}%"));
            });
        }

        $expenses = $query->orderByDesc('invoice_date')
            ->orderByDesc('created_at')
            ->get()
            ->transform(function ($expense) {
                $expense->created_by_name = $expense->user->name ?? 'Unknown';
                $expense->job = $expense->job ? [
                    'id'        => $expense->job->id,
                    'job_id'    => $expense->job->job_id,
                    'job_title' => $expense->job->job_title,
                ] : null;
                unset($expense->user);
                return $expense;
            });

        return response()->json(['data' => $expenses]);
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'service_job_id' => 'required|exists:service_jobs,id',
            'type'           => 'required|in:invoice,receipt',
            'title'          => 'nullable|string|max:255',
            'amount'         => 'required|numeric|min:0',
            'invoice_date'   => 'required|date',
            'file'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $uploadedFile    = $request->file('file');
        $fileName        = time() . '_' . $uploadedFile->getClientOriginalName();
        $destinationPath = public_path('uploads/documents/');

        if (!file_exists($destinationPath)) mkdir($destinationPath, 0755, true);

        $uploadedFile->move($destinationPath, $fileName);

        $expense = Document::create([
            'service_job_id' => $request->service_job_id,
            'created_by'     => auth()->id(),
            'type'           => $request->type,
            'title'          => $request->title,
            'amount'         => $request->amount,
            'invoice_date'   => $request->invoice_date,
            'file'           => '/uploads/documents/' . $fileName,
            'status'         => auth()->user()->getCreationStatusAttribute(),
        ]);

        return response()->json(['message' => 'Expense added successfully', 'data' => $expense], 201);
    }

    public function updateExpense(Request $request, $id)
    {
        $request->validate([
            'service_job_id' => 'nullable|exists:service_jobs,id',
            'type'           => 'required|in:invoice,receipt',
            'title'          => 'nullable|string|max:255',
            'amount'         => 'required|numeric|min:0',
            'invoice_date'   => 'required|date',
            'file'           => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $expense = Document::findOrFail($id);
        $docPath = $expense->file;

        if ($request->hasFile('file')) {
            if (file_exists(public_path($expense->file))) unlink(public_path($expense->file));

            $uploadedFile    = $request->file('file');
            $fileName        = time() . '_' . $uploadedFile->getClientOriginalName();
            $destinationPath = public_path('uploads/documents/');

            if (!file_exists($destinationPath)) mkdir($destinationPath, 0755, true);

            $uploadedFile->move($destinationPath, $fileName);
            $docPath = '/uploads/documents/' . $fileName;
        }

        $expense->update([
            'service_job_id' => $request->service_job_id,
            'type'           => $request->type,
            'title'          => $request->title,
            'amount'         => $request->amount,
            'invoice_date'   => $request->invoice_date,
            'file'           => $docPath,
        ]);

        return response()->json(['message' => 'Expense updated successfully', 'data' => $expense]);
    }

    public function getExpense($id)
    {
        $expense = Document::with('job:id,job_id,job_title')->findOrFail($id);
        return response()->json($expense);
    }

    public function deleteExpense($id)
    {
        $expense = Document::findOrFail($id);

        if (file_exists(public_path($expense->file))) {
            unlink(public_path($expense->file));
        }

        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully']);
    }

    public function detail($id)
    {
        $job  = ServiceJob::with('client:id,name')->findOrFail($id);
        $user = Auth::user();
        $isAdmin = !$user->hasRole('worker');

        $notes = $job->notes()->with('user:id,name')->get()->map(fn($n) => [
            'id'         => $n->id,
            'note'       => $n->note,
            'created_by' => $n->user->name ?? 'Unknown',
            'created_at' => $n->created_at->format('M d, H:i'),
        ]);

        $isWorker = $user->hasRole('worker');

        $documents = $job->documents()->with('user:id,name')
            ->where(function ($q) use ($user, $isWorker) {
                if (!$isWorker) {
                    $q->where('status', 'approved');
                } else {
                    $q->where('status', 'approved')
                        ->where('created_by', $user->id);
                }
            })
            ->get()->map(fn($d) => [
                'id'         => $d->id,
                'type'       => $d->type,
                'title'      => $d->title,
                'amount'     => $d->amount,
                'file_url'   => $d->file ? asset($d->file) : null,
                'created_by' => $d->user->name ?? 'Unknown',
                'created_at' => $d->created_at->format('M d, H:i'),
            ]);

        $checklists = ServiceJobChecklist::where('service_job_id', $id)
            ->with(['checklist.items', 'answers'])
            ->get()
            ->map(fn($sjc) => [
                'id'       => $sjc->id,
                'show_at'  => $sjc->show_at,
                'status'   => $sjc->status,
                'title'    => $sjc->checklist->title ?? '',
                'items'    => $sjc->checklist->items->map(fn($item) => [
                    'id'          => $item->id,
                    'question'    => $item->question,
                    'type'        => $item->type,
                    'is_required' => $item->is_required,
                    'answer'      => $sjc->answers->firstWhere('checklist_item_id', $item->id)?->answer,
                    'photo_path'  => $sjc->answers->firstWhere('checklist_item_id', $item->id)?->photo_path
                        ? asset($sjc->answers->firstWhere('checklist_item_id', $item->id)->photo_path)
                        : null,
                ]),
            ]);

        $availableChecklists = [];
        if ($isAdmin) {
            $availableChecklists = Checklist::where('is_active', 1)
                ->with('items')
                ->get()
                ->map(fn($c) => [
                    'id'    => $c->id,
                    'title' => $c->title,
                    'items' => $c->items->map(fn($i) => [
                        'question'    => $i->question,
                        'type'        => $i->type,
                        'is_required' => $i->is_required,
                    ]),
                ]);
        }

        $assignments = [];
        $timeLogs    = [];

        if ($isAdmin) {
            $assignments = $job->assignments()->with('worker:id,name')->get()->map(fn($a) => [
                'id'         => $a->id,
                'worker'     => $a->worker->name ?? '-',
                'date'       => $a->formatted_date ?? $a->date,
                'start_time' => $a->start_time,
                'end_time'   => $a->end_time,
                'note'       => $a->note,
            ]);

            $timeLogs = $job->timeLogs()->with('worker:id,name')->get()->map(fn($l) => [
                'id'              => $l->id,
                'worker'          => $l->worker->name ?? '-',
                'status'          => $l->status,
                'clock_in_at'     => $l->clock_in_at?->format('h:i A'),
                'clock_in_date'   => $l->clock_in_at?->format('d M Y'),
                'clock_out_at'    => $l->clock_out_at?->format('h:i A'),
                'clock_out_date'  => $l->clock_out_at?->format('d M Y'),
                'total_hours'     => $l->total_hours ? number_format($l->total_hours, 2) : null,
                'clock_in_photo'  => $l->clock_in_photo ? asset($l->clock_in_photo) : null,
                'clock_out_photo' => $l->clock_out_photo ? asset($l->clock_out_photo) : null,
                'clock_in_lat'    => $l->clock_in_lat,
                'clock_in_lng'    => $l->clock_in_lng,
            ]);
        }

        return response()->json([
            'job' => [
                'id'              => $job->id,
                'job_id'          => $job->job_id,
                'job_title'       => $job->job_title,
                'status'          => $job->status,
                'priority'        => $job->priority,
                'client_name'     => $job->client->name ?? '-',
                'description'     => $job->description,
                'instructions'    => $job->instructions,
                'address'         => collect([$job->address_line1, $job->address_line2, $job->city, $job->postcode])->filter()->implode(', '),
                'estimated_hours' => $job->estimated_hours,
                'start_date'      => $job->formattedStartDate(),
                'end_date'        => $job->formattedEndDate(),
                'created_at'      => $job->created_at->format('d M Y H:i'),
            ],
            'notes'                => $notes,
            'documents'            => $documents,
            'checklists'           => $checklists,
            'available_checklists' => $availableChecklists,
            'assignments'          => $assignments,
            'time_logs'            => $timeLogs,
        ]);
    }

    public function storeNote(Request $request, $id)
    {
        $request->validate(['note' => 'required|string']);
        ServiceJob::findOrFail($id);

        $note = Note::create([
            'service_job_id' => $id,
            'created_by'     => Auth::id(),
            'note'           => $request->note,
            'status'         => Auth::user()->creation_status,
        ]);

        return response()->json([
            'message' => 'Note added successfully',
            'note'    => [
                'id'         => $note->id,
                'note'       => $note->note,
                'created_by' => Auth::user()->name,
                'created_at' => $note->created_at->format('M d, H:i'),
            ]
        ], 201);
    }

    public function deleteNote($id, $noteId)
    {
        Note::where('service_job_id', $id)->findOrFail($noteId)->delete();
        return response()->json(['message' => 'Note deleted successfully']);
    }

    public function storeDocument(Request $request, $id)
    {
        $request->validate([
            'type'   => 'required|string',
            'title'  => 'nullable|string|max:255',
            'amount' => 'nullable|numeric',
            'file'   => 'required|file|max:5120',
        ]);

        ServiceJob::findOrFail($id);

        $uploadedFile = $request->file('file');
        $fileName     = time() . '_' . $uploadedFile->getClientOriginalName();
        $destPath     = public_path('uploads/documents/');
        if (!file_exists($destPath)) mkdir($destPath, 0755, true);
        $uploadedFile->move($destPath, $fileName);

        $user = Auth::user();
        Document::create([
            'service_job_id' => $id,
            'created_by'     => $user->id,
            'type'           => $request->type,
            'title'          => $request->title,
            'amount'         => in_array($request->type, ['invoice', 'receipt']) ? $request->amount : null,
            'file'           => '/uploads/documents/' . $fileName,
            'status'         => $user->creation_status,
        ]);

        $isWorker = $user->hasRole('worker');
        $message = $isWorker 
            ? 'Document uploaded and pending approval'
            : 'Document uploaded successfully';

        return response()->json([
            'message' => $message,
            'document' => []
        ], 201);
    }

    public function deleteDocument($id, $docId)
    {
        $doc = Document::where('service_job_id', $id)->findOrFail($docId);
        if ($doc->file && file_exists(public_path($doc->file))) unlink(public_path($doc->file));
        $doc->delete();
        return response()->json(['message' => 'Document deleted']);
    }

    public function assignChecklist(Request $request, $id)
    {
        $request->validate([
            'checklist_id' => 'required|exists:checklists,id',
            'show_at'      => 'required|in:clock_in,clock_out,both',
        ]);

        ServiceJob::findOrFail($id);

        if ($request->show_at === 'both') {
            foreach (['clock_in', 'clock_out'] as $showAt) {
                ServiceJobChecklist::create([
                    'service_job_id' => $id,
                    'checklist_id'   => $request->checklist_id,
                    'status'         => 'pending',
                    'show_at'        => $showAt,
                    'assigned_by'    => Auth::id(),
                ]);
            }
        } else {
            ServiceJobChecklist::create([
                'service_job_id' => $id,
                'checklist_id'   => $request->checklist_id,
                'status'         => 'pending',
                'show_at'        => $request->show_at,
                'assigned_by'    => Auth::id(),
            ]);
        }

        return response()->json(['message' => 'Checklist assigned successfully']);
    }

    public function removeChecklist($id, $assignmentId)
    {
        $assignment = ServiceJobChecklist::where('service_job_id', $id)
            ->with('answers')
            ->findOrFail($assignmentId);

        foreach ($assignment->answers as $answer) {
            if ($answer->photo_path && file_exists(public_path($answer->photo_path))) {
                unlink(public_path($answer->photo_path));
            }
            $answer->delete();
        }

        $assignment->delete();
        return response()->json(['message' => 'Checklist removed successfully']);
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

            $existing = ChecklistAnswer::where('service_job_checklist_id', $assignment->id)
                ->where('checklist_item_id', $itemId)
                ->first();

            if ($existing && $existing->photo_path) {
                $oldFile = public_path($existing->photo_path);
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            Image::make($file)->encode('webp', 75)->save($path . $filename);
            $photoPath = '/uploads/checklist-answers/' . $filename;

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

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Answers saved.']);
        }

        return back()->with('success', 'Answers saved.');
    }
}
