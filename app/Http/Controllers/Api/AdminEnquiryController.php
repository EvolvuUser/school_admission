<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\Request;

class AdminEnquiryController extends Controller
{
    /**
     * ============================================================
     * GET ALL ENQUIRIES - ADMIN
     * ============================================================
     *
     * GET:
     * /api/admin/admission/enquiries
     *
     * Admin can see all admission enquiries.
     */
    public function index(Request $request)
    {
        $query = Enquiry::query();

        /*
        |--------------------------------------------------------------------------
        | Optional Status Filter
        |--------------------------------------------------------------------------
        |
        | Example:
        | /api/admin/admission/enquiries?status=new
        |
        */

        if ($request->filled('status')) {
            $request->validate([
                'status' => [
                    'string',
                    'in:new,in_process,on_hold,approved,declined'
                ]
            ]);

            $query->where('status', $request->status);
        }

        /*
        |--------------------------------------------------------------------------
        | Optional Search
        |--------------------------------------------------------------------------
        |
        | Search by:
        | - enquiry number
        | - student name
        | - contact number
        | - email
        |
        */

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where(
                    'enquiry_number',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'first_name',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'last_name',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'contact_no',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'email',
                    'like',
                    '%' . $search . '%'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Get Enquiries
        |--------------------------------------------------------------------------
        */

        $enquiries = $query
            ->orderByDesc('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Gender Mapping
        |--------------------------------------------------------------------------
        */

        $genderMap = [
            'M' => 'Male',
            'F' => 'Female',
            'O' => 'Other',
        ];

        /*
        |--------------------------------------------------------------------------
        | Transform Response
        |--------------------------------------------------------------------------
        */

        $enquiries->transform(function ($enquiry) use ($genderMap) {

            return [

                'id' => $enquiry->id,

                'enquiry_number' =>
                    $enquiry->enquiry_number,

                'status' =>
                    $enquiry->status,

                /*
                |--------------------------------------------------------------------------
                | Student
                |--------------------------------------------------------------------------
                */

                'first_name' =>
                    $enquiry->first_name,

                'middle_name' =>
                    $enquiry->middle_name,

                'last_name' =>
                    $enquiry->last_name,

                'dob' =>
                    $enquiry->dob,

                'gender' =>
                    $genderMap[$enquiry->gender]
                    ?? $enquiry->gender,

                'class' =>
                    $enquiry->class,

                /*
                |--------------------------------------------------------------------------
                | Parent
                |--------------------------------------------------------------------------
                */

                'father_name' =>
                    $enquiry->father_name,

                'mother_name' =>
                    $enquiry->mother_name,

                /*
                |--------------------------------------------------------------------------
                | Contact
                |--------------------------------------------------------------------------
                */

                'contact_no' =>
                    $enquiry->contact_no,

                'email' =>
                    $enquiry->email,

                /*
                |--------------------------------------------------------------------------
                | School
                |--------------------------------------------------------------------------
                */

                'current_school' =>
                    $enquiry->current_school,

                /*
                |--------------------------------------------------------------------------
                | Address
                |--------------------------------------------------------------------------
                */

                'address' =>
                    $enquiry->address,

                'pincode' =>
                    $enquiry->pincode,

                /*
                |--------------------------------------------------------------------------
                | Sibling
                |--------------------------------------------------------------------------
                */

                'sibling_currently_studying' =>
                    $enquiry->sibling_currently_studying,

                /*
                |--------------------------------------------------------------------------
                | Documents
                |--------------------------------------------------------------------------
                */

                'all_documents_available' =>
                    $enquiry->all_documents_available,

                /*
                |--------------------------------------------------------------------------
                | Question
                |--------------------------------------------------------------------------
                */

                'question' =>
                    $enquiry->question,

                /*
                |--------------------------------------------------------------------------
                | Parent Registration ID
                |--------------------------------------------------------------------------
                */

                'nar_id' =>
                    $enquiry->nar_id,

                /*
                |--------------------------------------------------------------------------
                | Dates
                |--------------------------------------------------------------------------
                */

                'created_at' =>
                    $enquiry->created_at,

                'updated_at' =>
                    $enquiry->updated_at,
            ];
        });

        return response()->json([

            'success' => true,

            'data' => [

                'enquiries' =>
                    $enquiries,

            ]

        ]);
    }


    /**
     * ============================================================
     * GET SINGLE ENQUIRY - ADMIN
     * ============================================================
     *
     * GET:
     * /api/admin/admission/enquiries/{id}
     */
    public function show($id)
    {
        $enquiry = Enquiry::find($id);

        if (!$enquiry) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Admission enquiry not found.'

            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Gender Mapping
        |--------------------------------------------------------------------------
        */

        $genderMap = [
            'M' => 'Male',
            'F' => 'Female',
            'O' => 'Other',
        ];

        return response()->json([

            'success' => true,

            'data' => [

                'id' =>
                    $enquiry->id,

                'enquiry_number' =>
                    $enquiry->enquiry_number,

                'status' =>
                    $enquiry->status,

                /*
                |--------------------------------------------------------------------------
                | Student
                |--------------------------------------------------------------------------
                */

                'first_name' =>
                    $enquiry->first_name,

                'middle_name' =>
                    $enquiry->middle_name,

                'last_name' =>
                    $enquiry->last_name,

                'dob' =>
                    $enquiry->dob,

                'gender' =>
                    $genderMap[$enquiry->gender]
                    ?? $enquiry->gender,

                'class' =>
                    $enquiry->class,

                /*
                |--------------------------------------------------------------------------
                | Parent
                |--------------------------------------------------------------------------
                */

                'father_name' =>
                    $enquiry->father_name,

                'mother_name' =>
                    $enquiry->mother_name,

                /*
                |--------------------------------------------------------------------------
                | Contact
                |--------------------------------------------------------------------------
                */

                'contact_no' =>
                    $enquiry->contact_no,

                'email' =>
                    $enquiry->email,

                /*
                |--------------------------------------------------------------------------
                | School
                |--------------------------------------------------------------------------
                */

                'current_school' =>
                    $enquiry->current_school,

                /*
                |--------------------------------------------------------------------------
                | Address
                |--------------------------------------------------------------------------
                */

                'address' =>
                    $enquiry->address,

                'pincode' =>
                    $enquiry->pincode,

                /*
                |--------------------------------------------------------------------------
                | Sibling
                |--------------------------------------------------------------------------
                */

                'sibling_currently_studying' =>
                    $enquiry->sibling_currently_studying,

                /*
                |--------------------------------------------------------------------------
                | Documents
                |--------------------------------------------------------------------------
                */

                'all_documents_available' =>
                    $enquiry->all_documents_available,

                /*
                |--------------------------------------------------------------------------
                | Question
                |--------------------------------------------------------------------------
                */

                'question' =>
                    $enquiry->question,

                /*
                |--------------------------------------------------------------------------
                | Parent Registration ID
                |--------------------------------------------------------------------------
                */

                'nar_id' =>
                    $enquiry->nar_id,

                /*
                |--------------------------------------------------------------------------
                | Dates
                |--------------------------------------------------------------------------
                */

                'created_at' =>
                    $enquiry->created_at,

                'updated_at' =>
                    $enquiry->updated_at,

            ]

        ]);
    }


    /**
     * ============================================================
     * UPDATE ENQUIRY STATUS - ADMIN
     * ============================================================
     *
     * PUT:
     * /api/admin/admission/enquiries/{id}/status
     *
     * Allowed:
     *
     * new
     * in_process
     * on_hold
     * approved
     * declined
     */
    public function updateStatus(Request $request, $id)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Status
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'status' => [
                'required',
                'string',
                'in:new,in_process,on_hold,approved,declined'
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Find Enquiry
        |--------------------------------------------------------------------------
        */

        $enquiry = Enquiry::find($id);

        if (!$enquiry) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Admission enquiry not found.'

            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Previous Status
        |--------------------------------------------------------------------------
        */

        $oldStatus =
            $enquiry->status;

        /*
        |--------------------------------------------------------------------------
        | Update Status
        |--------------------------------------------------------------------------
        */

        $enquiry->status =
            $validated['status'];

        $enquiry->save();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' =>
                'Admission enquiry status updated successfully.',

            'data' => [

                'id' =>
                    $enquiry->id,

                'enquiry_number' =>
                    $enquiry->enquiry_number,

                'old_status' =>
                    $oldStatus,

                'status' =>
                    $enquiry->status,

                'updated_at' =>
                    $enquiry->updated_at,

            ]

        ]);
    }
}