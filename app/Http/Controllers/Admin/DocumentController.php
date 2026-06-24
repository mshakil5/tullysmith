<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ServiceJob;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'service_job_id' => 'required|exists:service_jobs,id',
            'type' => 'required|string',
            'title' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric',
            'file' => 'required|file|max:5120',
            'amount' => [
                'nullable',
                'numeric',
                Rule::when(
                    in_array($request->type, ['invoice', 'receipt']),
                    ['required', 'numeric', 'min:0.01']
                ),
            ],
        ]);

        $amount = null;
        if (in_array($request->type, ['invoice', 'receipt'])) {
            $amount = $request->amount;
        }

        $uploadedFile = $request->file('file');
        $fileName = time() . '_' . $uploadedFile->getClientOriginalName();

        $destinationPath = public_path('uploads/documents/');

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $uploadedFile->move($destinationPath, $fileName);

        $docPath = '/uploads/documents/' . $fileName;

        $doc = Document::create([
            'service_job_id' => $request->service_job_id,
            'created_by' => auth()->id(),
            'type' => $request->type,
            'title' => $request->title,
            'amount' => $amount,
            'invoice_date' => in_array($request->type, ['invoice', 'receipt']) ? now()->format('Y-m-d') : null,
            'file' => $docPath,
            'status' => auth()->user()->creation_status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'document' => [
                'id' => $doc->id,
                'type' => $doc->type,
                'title' => $doc->title,
                'amount' => $doc->amount,
                'file_url' => asset($doc->file),
                'created_by' => auth()->user()->name,
                'created_at' => $doc->created_at->format('M d, H:i'),
            ]
        ]);
    }

    public function getDocuments($jobId)
    {
        $job = ServiceJob::findOrFail($jobId);
        $user = auth()->user();
        $role = strtolower($user->getUserRole()->name ?? '');
        $isManager = in_array($role, ['super admin', 'line manager']);

        $docs = $job->documents()
            ->with('user:id,name')
            ->where(function ($q) use ($isManager, $user) {
                if ($isManager) {
                    $q->where('status', 'approved');
                } else {
                    $q->where('status', 'approved')
                        ->where('created_by', $user->id);
                }
            })
            ->get();

        return response()->json([
            'count' => $docs->count(),
            'documents' => $docs->map(function ($doc) {
                return [
                    'id'         => $doc->id,
                    'type'       => $doc->type,
                    'status'     => $doc->status,
                    'title'      => $doc->title,
                    'amount'     => $doc->amount,
                    'file_url'   => $doc->file ? asset($doc->file) : null,
                    'created_by' => $doc->user->name ?? 'Unknown',
                    'created_at' => $doc->created_at->format('M d, H:i'),
                ];
            })
        ]);
    }

    public function destroy($id)
    {
        $doc = Document::findOrFail($id);

        if ($doc->file && file_exists(public_path($doc->file))) {
            unlink(public_path($doc->file));
        }

        $doc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted'
        ]);
    }
}
