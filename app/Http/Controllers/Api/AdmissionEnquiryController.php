<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionEnquiryController extends Controller
{
    /**
     * Create a new admission enquiry.
     *
     * POST:
     * /api/admission/enquiries
     */
    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Student Details
            |--------------------------------------------------------------------------
            */

            'first_name' =>
                'required|string|max:100',

            'last_name' =>
                'nullable|string|max:100',

            'dob' =>
                'nullable|date',

            'gender' =>
                'required|string|in:M,F,O',

            /*
            |--------------------------------------------------------------------------
            | Class
            |--------------------------------------------------------------------------
            */

            'class_id' =>
                'required|integer',

            /*
            |--------------------------------------------------------------------------
            | Parent Details
            |--------------------------------------------------------------------------
            |
            | At least one parent's name is required.
            |
            */

            'father_name' =>
                'nullable|string|max:150',

            'mother_name' =>
                'nullable|string|max:150',

            /*
            |--------------------------------------------------------------------------
            | Contact
            |--------------------------------------------------------------------------
            */

            'contact_no' =>
                'required|string|max:20',

            'email' =>
                'nullable|email|max:150',

            /*
            |--------------------------------------------------------------------------
            | School
            |--------------------------------------------------------------------------
            */

            'current_school' =>
                'nullable|string|max:200',

            /*
            |--------------------------------------------------------------------------
            | Documents
            |--------------------------------------------------------------------------
            */

            'all_documents_available' =>
                'required|boolean',

            /*
            |--------------------------------------------------------------------------
            | Question
            |--------------------------------------------------------------------------
            */

            'question' =>
                'nullable|string|max:2000',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Validate Parent Name
        |--------------------------------------------------------------------------
        */

        if (
            empty($validated['father_name']) &&
            empty($validated['mother_name'])
        ) {

            return response()->json([

                'success' => false,

                'message' =>
                    'At least one parent name is required.',

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Check Class
        |--------------------------------------------------------------------------
        */

        $class = DB::table('class')
            ->where(
                'class_id',
                $validated['class_id']
            )
            ->first();


        if (!$class) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Selected class not found.',

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate Enquiry Number
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | ENQ-2026-0001
        | ENQ-2026-0002
        | ENQ-2026-0003
        |
        */

        $year = now()->format('Y');


        $lastEnquiry = Enquiry::where(
            'enquiry_number',
            'like',
            'ENQ-' . $year . '-%'
        )
        ->orderByDesc('id')
        ->first();


        if ($lastEnquiry) {

            $lastNumber = (int) substr(
                $lastEnquiry->enquiry_number,
                strrpos(
                    $lastEnquiry->enquiry_number,
                    '-'
                ) + 1
            );

            $nextNumber = $lastNumber + 1;

        } else {

            $nextNumber = 1;
        }


        $enquiryNumber =
            'ENQ-' .
            $year .
            '-' .
            str_pad(
                $nextNumber,
                4,
                '0',
                STR_PAD_LEFT
            );


        /*
        |--------------------------------------------------------------------------
        | Save Enquiry
        |--------------------------------------------------------------------------
        */

        $enquiry = Enquiry::create([

            'enquiry_number' =>
                $enquiryNumber,

            'first_name' =>
                trim($validated['first_name']),

            'last_name' =>
                isset($validated['last_name'])
                    ? trim($validated['last_name'])
                    : null,

            'dob' =>
                $validated['dob'] ?? null,

            'gender' =>
                strtoupper($validated['gender']),

            'class_id' =>
                $validated['class_id'],

            'father_name' =>
                isset($validated['father_name'])
                    ? trim($validated['father_name'])
                    : null,

            'mother_name' =>
                isset($validated['mother_name'])
                    ? trim($validated['mother_name'])
                    : null,

            'contact_no' =>
                trim($validated['contact_no']),

            'email' =>
                isset($validated['email'])
                    ? trim($validated['email'])
                    : null,

            'current_school' =>
                isset($validated['current_school'])
                    ? trim($validated['current_school'])
                    : null,

            'all_documents_available' =>
                $validated['all_documents_available'],

            'question' =>
                isset($validated['question'])
                    ? trim($validated['question'])
                    : null,

            'source' =>
                'WEBSITE',

            'status' =>
                'NEW',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' =>
                'Admission enquiry submitted successfully.',

            'data' => [

                'id' =>
                    $enquiry->id,

                'enquiry_number' =>
                    $enquiry->enquiry_number,

                'first_name' =>
                    $enquiry->first_name,

                'last_name' =>
                    $enquiry->last_name,

                'dob' =>
                    $enquiry->dob
                        ? $enquiry->dob->format('Y-m-d')
                        : null,

                'gender' =>
                    $enquiry->gender,

                'class_id' =>
                    $enquiry->class_id,

                'class_name' =>
                    $class->name ?? null,

                'father_name' =>
                    $enquiry->father_name,

                'mother_name' =>
                    $enquiry->mother_name,

                'contact_no' =>
                    $enquiry->contact_no,

                'email' =>
                    $enquiry->email,

                'current_school' =>
                    $enquiry->current_school,

                'all_documents_available' =>
                    $enquiry->all_documents_available,

                'question' =>
                    $enquiry->question,

                'source' =>
                    $enquiry->source,

                'status' =>
                    $enquiry->status,

                'created_at' =>
                    $enquiry->created_at,
            ]

        ], 201);
    }


    /**
     * Get all admission enquiries.
     *
     * GET:
     * /api/admission/enquiries
     */
    public function index()
    {
        $enquiries = Enquiry::orderByDesc('id')
            ->get();


        return response()->json([

            'success' => true,

            'data' => [

                'enquiries' =>
                    $enquiries,
            ]

        ]);
    }


    /**
     * Get one admission enquiry.
     *
     * GET:
     * /api/admission/enquiries/{id}
     */
    public function show($id)
    {
        $enquiry = Enquiry::find($id);


        if (!$enquiry) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Admission enquiry not found.',

            ], 404);
        }


        $class = DB::table('class')
            ->where(
                'class_id',
                $enquiry->class_id
            )
            ->first();


        return response()->json([

            'success' => true,

            'data' => [

                'id' =>
                    $enquiry->id,

                'enquiry_number' =>
                    $enquiry->enquiry_number,

                'first_name' =>
                    $enquiry->first_name,

                'last_name' =>
                    $enquiry->last_name,

                'dob' =>
                    $enquiry->dob
                        ? $enquiry->dob->format('Y-m-d')
                        : null,

                'gender' =>
                    $enquiry->gender,

                'class_id' =>
                    $enquiry->class_id,

                'class_name' =>
                    $class->name ?? null,

                'father_name' =>
                    $enquiry->father_name,

                'mother_name' =>
                    $enquiry->mother_name,

                'contact_no' =>
                    $enquiry->contact_no,

                'email' =>
                    $enquiry->email,

                'current_school' =>
                    $enquiry->current_school,

                'all_documents_available' =>
                    $enquiry->all_documents_available,

                'question' =>
                    $enquiry->question,

                'source' =>
                    $enquiry->source,

                'status' =>
                    $enquiry->status,

                'notes' =>
                    $enquiry->notes,

                'conversion_reference' =>
                    $enquiry->conversion_reference,

                'created_at' =>
                    $enquiry->created_at,

                'updated_at' =>
                    $enquiry->updated_at,
            ]

        ]);
    }
}