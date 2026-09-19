<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdmissionEnquiryController extends Controller
{
    /**
     * Get all classes for Admission Enquiry.
     *
     * GET:
     * /api/admission/enquiry/classes
     *
     * This returns ALL classes from the class table.
     */
    public function getClasses()
    {
        $classes = DB::table('class')
            ->select(
                'class_id',
                'name'
            )
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,

            'data' => [
                'classes' => $classes
            ]
        ]);
    }


    /**
     * Create Admission Enquiry.
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

            'first_name' => [
                'required',
                'string',
                'max:100'
            ],

            'last_name' => [
                'required',
                'string',
                'max:100'
            ],

            'dob' => [
                'required'
            ],

            'gender' => [
                'required',
                'string',
                'in:Male,Female,Other'
            ],

            /*
             * IMPORTANT:
             *
             * Frontend sends:
             *
             * UKG
             * Grade 1
             * Grade 2
             *
             * NOT class_id.
             */
            'class' => [
                'required',
                'string',
                'max:100'
            ],


            /*
            |--------------------------------------------------------------------------
            | Parent Details
            |--------------------------------------------------------------------------
            */

            'father_name' => [
                'nullable',
                'string',
                'max:100'
            ],

            'mother_name' => [
                'nullable',
                'string',
                'max:100'
            ],


            /*
            |--------------------------------------------------------------------------
            | Contact Details
            |--------------------------------------------------------------------------
            */

            'contact_no' => [
                'required',
                'string',
                'max:20'
            ],

            'email' => [
                'nullable',
                'email',
                'max:150'
            ],


            /*
            |--------------------------------------------------------------------------
            | School Details
            |--------------------------------------------------------------------------
            */

            'current_school' => [
                'nullable',
                'string',
                'max:200'
            ],


            /*
            |--------------------------------------------------------------------------
            | Documents
            |--------------------------------------------------------------------------
            */

            'all_documents_available' => [
                'nullable'
            ],


            /*
            |--------------------------------------------------------------------------
            | Question
            |--------------------------------------------------------------------------
            */

            'question' => [
                'nullable',
                'string'
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Validate Parent Name
        |--------------------------------------------------------------------------
        |
        | At least one parent name is required.
        |
        */

        if (
            empty($validated['father_name']) &&
            empty($validated['mother_name'])
        ) {

            return response()->json([

                'success' => false,

                'message' =>
                    'At least one parent name is required.'

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Find Selected Class
        |--------------------------------------------------------------------------
        |
        | The frontend sends the class NAME.
        |
        | Example:
        |
        | "class": "UKG"
        |
        | We verify that this class exists in the class table.
        |
        */

        $className = trim($validated['class']);

        $class = DB::table('class')
            ->where('name', $className)
            ->first();


        if (!$class) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Selected class not found.'

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Convert Gender
        |--------------------------------------------------------------------------
        |
        | Frontend:
        |
        | Male
        | Female
        | Other
        |
        | Database:
        |
        | M
        | F
        | O
        |
        */

        $genderMap = [

            'Male' => 'M',

            'Female' => 'F',

            'Other' => 'O',
        ];


        $gender = $genderMap[
            $validated['gender']
        ];


        /*
        |--------------------------------------------------------------------------
        | Convert Date of Birth
        |--------------------------------------------------------------------------
        |
        | Frontend:
        |
        | 02/06/2024
        |
        | Database:
        |
        | 2024-02-06
        |
        */

        try {

            $dob = Carbon::createFromFormat(
                'm/d/Y',
                $validated['dob']
            )->format('Y-m-d');

        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Invalid date of birth. Please use MM/DD/YYYY format.'

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Convert Documents Value
        |--------------------------------------------------------------------------
        |
        | Frontend may send:
        |
        | true
        | false
        |
        | or:
        |
        | "true"
        | "false"
        |
        */

        $documentsAvailable = $request->boolean(
            'all_documents_available'
        );


        /*
        |--------------------------------------------------------------------------
        | Save Enquiry
        |--------------------------------------------------------------------------
        */

        $enquiry = Enquiry::create([

            /*
            |--------------------------------------------------------------------------
            | Student
            |--------------------------------------------------------------------------
            */

            'first_name' =>
                trim($validated['first_name']),

            'last_name' =>
                trim($validated['last_name']),

            'dob' =>
                $dob,

            'gender' =>
                $gender,


            /*
            |--------------------------------------------------------------------------
            | Class
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | We save the CLASS NAME.
            |
            | Example:
            |
            | UKG
            |
            | NOT:
            |
            | 150
            |
            */

            'class' =>
                $class->name,


            /*
            |--------------------------------------------------------------------------
            | Parent
            |--------------------------------------------------------------------------
            */

            'father_name' =>
                !empty($validated['father_name'])
                    ? trim($validated['father_name'])
                    : null,

            'mother_name' =>
                !empty($validated['mother_name'])
                    ? trim($validated['mother_name'])
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Contact
            |--------------------------------------------------------------------------
            */

            'contact_no' =>
                trim($validated['contact_no']),

            'email' =>
                $validated['email'] ?? null,


            /*
            |--------------------------------------------------------------------------
            | School
            |--------------------------------------------------------------------------
            */

            'current_school' =>
                isset($validated['current_school'])
                    ? trim($validated['current_school'])
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Documents
            |--------------------------------------------------------------------------
            */

            'all_documents_available' =>
                $documentsAvailable ? 'Y' : 'N',


            /*
            |--------------------------------------------------------------------------
            | Question
            |--------------------------------------------------------------------------
            */

            'question' =>
                $validated['question'] ?? null,
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

                'first_name' =>
                    $enquiry->first_name,

                'last_name' =>
                    $enquiry->last_name,

                'dob' =>
                    $enquiry->dob,

                /*
                 * Return frontend-friendly value.
                 */
                'gender' =>
                    $validated['gender'],

                /*
                 * Return class NAME.
                 */
                'class' =>
                    $enquiry->class,

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


        /*
        |--------------------------------------------------------------------------
        | Convert Gender For Frontend
        |--------------------------------------------------------------------------
        */

        $enquiries->transform(function ($enquiry) {

            $genderMap = [

                'M' => 'Male',

                'F' => 'Female',

                'O' => 'Other',
            ];


            return [

                'id' =>
                    $enquiry->id,

                'first_name' =>
                    $enquiry->first_name,

                'last_name' =>
                    $enquiry->last_name,

                'dob' =>
                    $enquiry->dob,

                'gender' =>
                    $genderMap[
                        $enquiry->gender
                    ] ?? $enquiry->gender,

                /*
                 * Class is already stored as NAME.
                 */
                'class' =>
                    $enquiry->class,

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
     * Get single admission enquiry.
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
                    'Admission enquiry not found.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Gender For Frontend
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

                'first_name' =>
                    $enquiry->first_name,

                'last_name' =>
                    $enquiry->last_name,

                'dob' =>
                    $enquiry->dob,

                'gender' =>
                    $genderMap[
                        $enquiry->gender
                    ] ?? $enquiry->gender,

                /*
                 * Class NAME.
                 */
                'class' =>
                    $enquiry->class,

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

                'created_at' =>
                    $enquiry->created_at,

                'updated_at' =>
                    $enquiry->updated_at,
            ]

        ]);
    }
}