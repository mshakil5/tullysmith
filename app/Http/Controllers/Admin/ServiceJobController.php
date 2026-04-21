<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ServiceJob;
use App\Models\User;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;

class ServiceJobController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $view = $request->view;
            
            $jobs = ServiceJob::with('client:id,name')
                ->select([
                    'id', 'job_id', 'job_title', 'client_id',
                    'address_line1', 'address_line2', 'city', 'postcode',
                    'status', 'priority', 'start_date', 'end_date',
                    'estimated_hours', 'created_at'
                ])
                ->when($view === 'confirmed', fn($q) => $q->where('status', 'confirmed'))
                ->when($view !== 'confirmed', fn($q) => $q->where('status', '!=', 'confirmed'))
                ->when($request->status && $view !== 'confirmed', fn($q) => $q->where('status', $request->status))
                ->orderByDesc('id');

            return DataTables::of($jobs)
                ->addIndexColumn()

                ->addColumn('client', function ($row) {
                    return $row->client->name ?? '';
                })

                ->addColumn('total_amount', function ($row) {
                    $total = $row->totalExpenses();
                    $count = $row->documents()
                        ->whereIn('type', ['invoice', 'receipt'])
                        ->where('status', 'approved')
                        ->count();

                    $label = $count . ' Expense' . ($count == 1 ? '' : 's');

                    return '<a href="' . route('expenses.index', ['job_id' => $row->id]) . '" class="btn btn-soft-primary btn-sm">
                                <i class="ri-money-pound-circle-line me-1"></i>
                                ' . $label . '
                                <span class="badge bg-primary ms-1">£' . number_format($total, 2) . '</span>
                            </a>';
                })
                ->addColumn('status', function ($row) {
                    $color = match ($row->status) {
                        'draft' => 'secondary',
                        'active' => 'success',
                        'pending' => 'warning',
                        'completed' => 'primary',
                        'confirmed' => 'info',
                        default => 'dark',
                    };
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->status) . '</span>';
                })

                ->addColumn('priority', function ($row) {
                    $color = match ($row->priority) {
                        'low' => 'success',
                        'medium' => 'warning',
                        'high' => 'danger',
                        default => 'secondary',
                    };
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->priority) . '</span>';
                })

                ->addColumn('start_date', function ($row) {
                    return $row->formattedStartDate();
                })

                ->addColumn('end_date', function ($row) {
                    return $row->formattedEndDate();
                })

                ->addColumn('estimated_hours', function ($row) {
                    return ($row->estimated_hours ?? 0) . ' hrs';
                })

                ->addColumn('action', function ($row) use ($view) {
                    if ($view === 'confirmed') {
                        return '
                            <a class="btn btn-soft-primary btn-sm" href="' . route('serviceJob.show', $row->id) . '">
                                <i class="ri-eye-fill"></i> View
                            </a>';
                    }
                    
                    return '
                        <div class="dropdown">
                        <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                            <i class="ri-more-fill"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="' . route('serviceJob.show', $row->id) . '">
                                    <i class="ri-eye-fill me-2"></i>View
                                </a>
                            </li>
                            <li class="dropdown-divider"></li>
                            <li>
                            <button class="dropdown-item EditBtn" data-id="' . $row->id . '">
                                <i class="ri-pencil-fill me-2"></i>Edit
                            </button>
                            </li>
                            <li class="dropdown-divider"></li>
                            <li>
                            <button class="dropdown-item deleteBtn"
                                data-delete-url="' . route('serviceJob.delete', $row->id) . '"
                                data-method="DELETE"
                                data-table="#serviceJobTable">
                                <i class="ri-delete-bin-fill me-2"></i>Delete
                            </button>
                            </li>
                        </ul>
                        </div>';
                })

                ->rawColumns(['action', 'status', 'priority', 'total_amount'])
                ->make(true);
        }

        $clients = User::where('user_type', 0)->select('id', 'name')->latest()->get();
        $view = $request->get('view');

        return view('admin.service_jobs.index', compact('clients', 'view'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_title'      => 'required|string|max:255',
            'client_id'      => 'required|integer|exists:users,id',
            'description'    => 'nullable|string',
            'instructions'   => 'nullable|string',
            'status'         => 'required|string|max:50',
            'priority'       => 'required|string|max:50',
            'address_line1'  => 'nullable|string|max:255',
            'address_line2'  => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'postcode'       => 'required|string|max:20',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'estimated_hours'=> 'nullable|numeric',
        ]);

        ServiceJob::create([
            'job_id'          => 'JOB-' . time(),
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

        return response()->json(['message' => 'Job created successfully.']);
    }

    public function edit($id)
    {
        $job = ServiceJob::with('client')->findOrFail($id);
        return response()->json($job);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'             => 'required|exists:service_jobs,id',
            'job_title'      => 'required|string|max:255',
            'client_id'      => 'required|integer|exists:users,id',
            'description'    => 'nullable|string',
            'instructions'   => 'nullable|string',
            'status'         => 'required|string|max:50',
            'priority'       => 'required|string|max:50',
            'address_line1'  => 'nullable|string|max:255',
            'address_line2'  => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'postcode'       => 'required|string|max:20',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'estimated_hours'=> 'nullable|numeric',
        ]);

        $job = ServiceJob::findOrFail($request->id);

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

        return response()->json(['message' => 'Job updated successfully.']);
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

        return response()->json(['message' => 'Job deleted successfully.']);
    }

    public function show($id)
    {
        $job = ServiceJob::with('client', 'assignments.worker', 'timeLogs.worker')->findOrFail($id);
        return view('admin.service_jobs.show', compact('job'));
    }

    public function expenses(Request $request)
    {
        if ($request->ajax()) {
            $jobId = $request->job_id;
            
            $expenses = Document::with(['job:id,job_id,job_title', 'job.client:id,name', 'user:id,name'])
                ->whereIn('type', ['invoice', 'receipt'])
                ->where('status', 'approved')
                ->when($jobId, fn($q) => $q->where('service_job_id', $jobId))
                ->select(['id', 'service_job_id', 'created_by', 'type', 'title', 'amount', 'invoice_date', 'file', 'created_at'])
                ->orderByDesc('invoice_date')
                ->orderByDesc('created_at');

            return DataTables::of($expenses)
                ->addIndexColumn()
                
                ->addColumn('job', function ($row) {
                    return '<a href="' . route('serviceJob.show', $row->service_job_id) . '" class="text-primary fw-semibold">' 
                        . ($row->job->job_id ?? 'N/A') . '</a><br>'
                        . '<small class="text-muted">' . ($row->job->job_title ?? '') . '</small>';
                })
                
                ->addColumn('client', function ($row) {
                    return $row->job->client->name ?? '-';
                })
                
                ->addColumn('type', function ($row) {
                    $color = $row->type === 'invoice' ? 'primary' : 'info';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->type) . '</span>';
                })
                
                ->addColumn('title', function ($row) {
                    return $row->title ?? '<span class="text-muted">Untitled</span>';
                })
                
                ->addColumn('amount', function ($row) {
                    return '<strong class="text-success">£' . number_format($row->amount, 2) . '</strong>';
                })
                
                ->addColumn('invoice_date', function ($row) {
                    return $row->invoice_date ? Carbon::parse($row->invoice_date)->format('d M Y') : '-';
                })
                
                ->addColumn('created_by', function ($row) {
                    return $row->user->name ?? 'Unknown';
                })
                
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('d M Y, h:i A');
                })
                
                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                                <i class="ri-more-fill"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="' . asset($row->file) . '" target="_blank">
                                        <i class="ri-eye-fill me-2"></i>View File
                                    </a>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item EditExpenseBtn" data-id="' . $row->id . '">
                                        <i class="ri-pencil-fill me-2"></i>Edit
                                    </button>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item deleteExpenseBtn" data-id="' . $row->id . '">
                                        <i class="ri-delete-bin-fill me-2"></i>Delete
                                    </button>
                                </li>
                            </ul>
                        </div>';
                })
                
                ->rawColumns(['job', 'type', 'title', 'amount', 'action'])
                ->make(true);
        }

        $jobId = $request->get('job_id');
        $job = null;
        $jobs = ServiceJob::select('id', 'job_id', 'job_title')->orderByDesc('id')->get();
        
        if ($jobId) {
            $job = ServiceJob::with('client')->findOrFail($jobId);
        }

        return view('admin.expenses.index', compact('job', 'jobs'));
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'service_job_id' => 'required|exists:service_jobs,id',
            'type' => 'required|in:invoice,receipt',
            'title' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'invoice_date' => 'required|date',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $uploadedFile = $request->file('file');
        $fileName = time() . '_' . $uploadedFile->getClientOriginalName();
        $destinationPath = public_path('uploads/documents/');

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $uploadedFile->move($destinationPath, $fileName);
        $docPath = '/uploads/documents/' . $fileName;

        Document::create([
            'service_job_id' => $request->service_job_id,
            'created_by' => auth()->id(),
            'type' => $request->type,
            'title' => $request->title,
            'amount' => $request->amount,
            'invoice_date' => $request->invoice_date,
            'file' => $docPath,
            'status' => 'approved',
        ]);

        return response()->json(['message' => 'Expense added successfully']);
    }

    public function editExpense($id)
    {
        $expense = Document::with('job:id,job_id,job_title')->findOrFail($id);
        return response()->json($expense);
    }

    public function updateExpense(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:documents,id',
            'service_job_id' => 'required|exists:service_jobs,id',
            'type' => 'required|in:invoice,receipt',
            'title' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'invoice_date' => 'required|date',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $expense = Document::findOrFail($request->id);

        $docPath = $expense->file;

        if ($request->hasFile('file')) {
            // Delete old file
            if (file_exists(public_path($expense->file))) {
                unlink(public_path($expense->file));
            }

            $uploadedFile = $request->file('file');
            $fileName = time() . '_' . $uploadedFile->getClientOriginalName();
            $destinationPath = public_path('uploads/documents/');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $uploadedFile->move($destinationPath, $fileName);
            $docPath = '/uploads/documents/' . $fileName;
        }

        $expense->update([
            'service_job_id' => $request->service_job_id,
            'type' => $request->type,
            'title' => $request->title,
            'amount' => $request->amount,
            'invoice_date' => $request->invoice_date,
            'file' => $docPath,
        ]);

        return response()->json(['message' => 'Expense updated successfully']);
    }

    public function destroyExpense($id)
    {
        $expense = Document::findOrFail($id);
        
        if (file_exists(public_path($expense->file))) {
            unlink(public_path($expense->file));
        }
        
        $expense->delete();
        
        return response()->json(['message' => 'Expense deleted successfully']);
    }
}