<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $contacts = Contact::select(['id','first_name','last_name','email','subject','message','status','created_at'])
                ->orderBy('status','asc')->orderByDesc('id');

            return DataTables::of($contacts)
                ->addIndexColumn()
                ->addColumn('full_name', fn($row) => $row->first_name.' '.$row->last_name)
                ->addColumn('date', fn($row) => Carbon::parse($row->created_at)->format('d-m-Y'))
                ->addColumn('status', function($row){
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="form-check form-switch">
                                <input class="form-check-input toggle-status" data-id="'.$row->id.'" type="checkbox" '.$checked.'>
                            </div>';
                })
                ->addColumn('action', function($row){
                    return '
                        <div class="dropdown">
                          <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown"><i class="ri-more-fill"></i></button>
                          <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item viewBtn" data-id="'.$row->id.'"><i class="ri-eye-fill me-2"></i>View</button></li>
                            <li class="dropdown-divider"></li>
                            <li><button class="dropdown-item deleteBtn" data-delete-url="'.route('contacts.delete',$row->id).'" data-method="DELETE" data-table="#contactTable"><i class="ri-delete-bin-fill me-2"></i>Delete</button></li>
                          </ul>
                        </div>';
                })
                ->rawColumns(['status','action'])
                ->make(true);
        }

        return view('admin.contacts.index');
    }

    public function show($id)
    {
        $contact = Contact::find($id);
        if (!$contact) return response()->json(['message' => 'Not found'], 404);

        $contact->formatted_date = $contact->created_at->format('d-m-Y | H:i:s');
        return response()->json($contact);
    }

    public function destroy($id)
    {
        $contact = Contact::find($id);
        if (!$contact) return response()->json(['message' => 'Not found'], 404);

        $contact->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }

    public function toggleStatus(Request $request)
    {
        $contact = Contact::find($request->id);
        if (!$contact) return response()->json(['message' => 'Not found'], 404);

        $contact->status = $request->status;
        $contact->save();

        return response()->json(['message' => 'Status updated successfully.']);
    }
}