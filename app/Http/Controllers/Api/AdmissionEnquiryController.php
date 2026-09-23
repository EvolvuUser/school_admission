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
     * ============================================================
     * GET ALL CLASSES
     * ============================================================
     *
     * GET:
     * /api/admission/enquiry/classes
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
     * ============================================================
     * GET GENDER OPTIONS
     * ============================================================
     *
     * GET:
     * /api/admission/enquiry/genders
     */
    public function getGenders()
    {
        $genders = DB::table('admission_form_field_options')
            ->where('field_name', 'gender')
            ->where('is_active', 'Y')
            ->orderBy('display_order')
            ->get([
                'field_option_id',
                'option_value'
            ]);

        return response()->json([
            'success' => true,

            'data' => [
                'genders' => $genders
            ]
        ]);
    }


    /**
     * ============================================================
     * CREATE ADMISSION ENQUIRY
     * ============================================================
     *
     * POST:
     * /api/admission/enquiries
     *
     * nar_id is currently received from the frontend,
     * same as the current admission form approach.
     */
    public function store(Request $request)
    {

        if (!$request->has('nar_id') && $request->has('narId')) {
    $request->merge([
        'nar_id' => $request->input('narId')
    ]);
}
        /*
        |--------------------------------------------------------------------------
        | Normalize Gender
        |--------------------------------------------------------------------------
        */

        $genderInput = trim(
            (string) $request->input('gender')
        );

        $genderNormalized = strtolower($genderInput);

        $genderMap = [
            'male'   => 'Male',
            'female' => 'Female',
            'other'  => 'Other',

            'm' => 'Male',
            'f' => 'Female',
            'o' => 'Other',
        ];

        if (isset($genderMap[$genderNormalized])) {

            $request->merge([
                'gender' => $genderMap[$genderNormalized]
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Normalize Class
        |--------------------------------------------------------------------------
        */

        if ($request->has('class')) {

            $request->merge([
                'class' => trim(
                    (string) $request->input('class')
                )
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Normalize String Fields
        |--------------------------------------------------------------------------
        */

        foreach ([
            'first_name',
            'middle_name',
            'last_name',
            'father_name',
            'mother_name',
            'contact_no',
            'current_school',
            'address',
            'pincode',
            'email',
            'question'
        ] as $field) {

            if ($request->has($field)) {

                $value = $request->input($field);

                $request->merge([
                    $field => is_string($value)
                        ? trim($value)
                        : $value
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Normalize All Documents Available
        |--------------------------------------------------------------------------
        |
        | Database:
        |
        | Y = Yes
        | N = No
        |
        */

        if ($request->has('all_documents_available')) {

            $documentsInput =
                $request->input('all_documents_available');

            if (
                $documentsInput === true ||
                $documentsInput === 1 ||
                $documentsInput === '1' ||
                strtolower((string) $documentsInput) === 'y' ||
                strtolower((string) $documentsInput) === 'yes' ||
                strtolower((string) $documentsInput) === 'true'
            ) {

                $request->merge([
                    'all_documents_available' => 'Y'
                ]);

            } elseif (
                $documentsInput === false ||
                $documentsInput === 0 ||
                $documentsInput === '0' ||
                strtolower((string) $documentsInput) === 'n' ||
                strtolower((string) $documentsInput) === 'no' ||
                strtolower((string) $documentsInput) === 'false'
            ) {

                $request->merge([
                    'all_documents_available' => 'N'
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Normalize Sibling Checkbox
        |--------------------------------------------------------------------------
        |
        | Frontend can send:
        |
        | true / false
        | 1 / 0
        | Y / N
        | yes / no
        |
        | Database stores:
        |
        | Y / N
        |
        */

        if ($request->has('sibling_currently_studying')) {

            $siblingInput =
                $request->input(
                    'sibling_currently_studying'
                );

            if (
                $siblingInput === true ||
                $siblingInput === 1 ||
                $siblingInput === '1' ||
                strtolower((string) $siblingInput) === 'y' ||
                strtolower((string) $siblingInput) === 'yes' ||
                strtolower((string) $siblingInput) === 'true'
            ) {

                $request->merge([
                    'sibling_currently_studying' => 'Y'
                ]);

            } elseif (
                $siblingInput === false ||
                $siblingInput === 0 ||
                $siblingInput === '0' ||
                strtolower((string) $siblingInput) === 'n' ||
                strtolower((string) $siblingInput) === 'no' ||
                strtolower((string) $siblingInput) === 'false'
            ) {

                $request->merge([
                    'sibling_currently_studying' => 'N'
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Parent / Registration
            |--------------------------------------------------------------------------
            */

            'nar_id' => [
                'required',
                'integer'
            ],


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

            'middle_name' => [
                'nullable',
                'string',
                'max:100'
            ],

            'last_name' => [
                'required',
                'string',
                'max:100'
            ],

            'dob' => [
                'required',
                'string'
            ],

            'gender' => [
                'required',
                'string',
                'in:Male,Female,Other'
            ],

            /*
             * Frontend sends class name.
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
            | Address
            |--------------------------------------------------------------------------
            */

            'address' => [
                'nullable',
                'string'
            ],

            'pincode' => [
                'nullable',
                'string',
                'max:10'
            ],


            /*
            |--------------------------------------------------------------------------
            | Sibling
            |--------------------------------------------------------------------------
            */

            'sibling_currently_studying' => [
                'nullable',
                'string',
                'in:Y,N'
            ],


            /*
            |--------------------------------------------------------------------------
            | Documents
            |--------------------------------------------------------------------------
            */

            'all_documents_available' => [
                'nullable',
                'string',
                'in:Y,N'
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
        | Get Parent nar_id
        |--------------------------------------------------------------------------
        */

        $narId = $validated['nar_id'];


        /*
        |--------------------------------------------------------------------------
        | At Least One Parent Name Required
        |--------------------------------------------------------------------------
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
        */

        $className =
            trim($validated['class']);

        $class = DB::table('class')
            ->whereRaw(
                'LOWER(name) = ?',
                [strtolower($className)]
            )
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
        | Convert Gender For Database
        |--------------------------------------------------------------------------
        */

        $genderMapForDatabase = [
            'Male'   => 'M',
            'Female' => 'F',
            'Other'  => 'O',
        ];

        $gender =
            $genderMapForDatabase[
                $validated['gender']
            ];


        /*
        |--------------------------------------------------------------------------
        | Convert Date Of Birth
        |--------------------------------------------------------------------------
        */

        try {

            $dob = Carbon::createFromFormat(
                'm/d/Y',
                trim($validated['dob'])
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
        | Documents Value
        |--------------------------------------------------------------------------
        */

        $documentsAvailable =
            $validated['all_documents_available'] ?? 'N';


        /*
        |--------------------------------------------------------------------------
        | Sibling Value
        |--------------------------------------------------------------------------
        */

        $siblingCurrentlyStudying =
            $validated['sibling_currently_studying'] ?? 'N';


        /*
        |--------------------------------------------------------------------------
        | Generate Enquiry Number
        |--------------------------------------------------------------------------
        */

        $year =
            now()->format('Y');

        $lastEnquiry =
            Enquiry::where(
                'enquiry_number',
                'like',
                'ENQ-' . $year . '-%'
            )
            ->orderByDesc('id')
            ->first();


        if ($lastEnquiry) {

            $lastNumber =
                (int) substr(
                    $lastEnquiry->enquiry_number,
                    strrpos(
                        $lastEnquiry->enquiry_number,
                        '-'
                    ) + 1
                );

            $nextNumber =
                $lastNumber + 1;

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

        $enquiry =
            Enquiry::create([

                /*
                |--------------------------------------------------------------------------
                | Parent / Registration
                |--------------------------------------------------------------------------
                */

                'nar_id' =>
                    $narId,


                /*
                |--------------------------------------------------------------------------
                | Enquiry Number
                |--------------------------------------------------------------------------
                */

                'enquiry_number' =>
                    $enquiryNumber,


                /*
                |--------------------------------------------------------------------------
                | Student Details
                |--------------------------------------------------------------------------
                */

                'first_name' =>
                    trim(
                        $validated['first_name']
                    ),

                'middle_name' =>
                    !empty($validated['middle_name'])
                        ? trim($validated['middle_name'])
                        : null,

                'last_name' =>
                    trim(
                        $validated['last_name']
                    ),

                'dob' =>
                    $dob,

                'gender' =>
                    $gender,


                /*
                |--------------------------------------------------------------------------
                | Class
                |--------------------------------------------------------------------------
                */

                'class' =>
                    $class->name,


                /*
                |--------------------------------------------------------------------------
                | Parent Details
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
                | Contact Details
                |--------------------------------------------------------------------------
                */

                'contact_no' =>
                    trim(
                        $validated['contact_no']
                    ),

                'email' =>
                    $validated['email'] ?? null,


                /*
                |--------------------------------------------------------------------------
                | School Details
                |--------------------------------------------------------------------------
                */

                'current_school' =>
                    isset($validated['current_school'])
                        ? trim($validated['current_school'])
                        : null,


                /*
                |--------------------------------------------------------------------------
                | Address
                |--------------------------------------------------------------------------
                */

                'address' =>
                    isset($validated['address'])
                        ? trim($validated['address'])
                        : null,

                'pincode' =>
                    isset($validated['pincode'])
                        ? trim($validated['pincode'])
                        : null,


                /*
                |--------------------------------------------------------------------------
                | Sibling
                |--------------------------------------------------------------------------
                */

                'sibling_currently_studying' =>
                    $siblingCurrentlyStudying,


                /*
                |--------------------------------------------------------------------------
                | Documents
                |--------------------------------------------------------------------------
                */

                'all_documents_available' =>
                    $documentsAvailable,


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
        | Success Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                true,

            'message' =>
                'Admission enquiry submitted successfully.',

            'data' => [

                'id' =>
                    $enquiry->id,

                'enquiry_number' =>
                    $enquiry->enquiry_number,


                /*
                | Student
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
                    $validated['gender'],

                'class' =>
                    $enquiry->class,


                /*
                | Parent
                */

                'father_name' =>
                    $enquiry->father_name,

                'mother_name' =>
                    $enquiry->mother_name,


                /*
                | Contact
                */

                'contact_no' =>
                    $enquiry->contact_no,

                'email' =>
                    $enquiry->email,


                /*
                | School
                */

                'current_school' =>
                    $enquiry->current_school,


                /*
                | Address
                */

                'address' =>
                    $enquiry->address,

                'pincode' =>
                    $enquiry->pincode,


                /*
                | Sibling
                */

                'sibling_currently_studying' =>
                    $enquiry->sibling_currently_studying,


                /*
                | Documents
                */

                'all_documents_available' =>
                    $enquiry->all_documents_available,


                /*
                | Question
                */

                'question' =>
                    $enquiry->question,

                'created_at' =>
                    $enquiry->created_at,
            ]

        ], 201);
    }


    /**
     * ============================================================
     * GET ALL ADMISSION ENQUIRIES
     * ============================================================
     *
     * GET:
     * /api/admission/enquiries
     *
     * The frontend sends nar_id for now,
     * matching the existing form implementation.
     *
     * Only enquiries belonging to that nar_id are returned.
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate nar_id
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'nar_id' => [
                'required',
                'integer'
            ]
        ]);

        $narId = $validated['nar_id'];


        /*
        |--------------------------------------------------------------------------
        | Get Only This Parent's Enquiries
        |--------------------------------------------------------------------------
        */

        $enquiries = Enquiry::where(
            'nar_id',
            $narId
        )
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

        $enquiries->transform(
            function ($enquiry) use ($genderMap) {

                return [

                    'id' =>
                        $enquiry->id,

                    'enquiry_number' =>
                        $enquiry->enquiry_number,


                    /*
                    | Student
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
                        $genderMap[
                            $enquiry->gender
                        ] ?? $enquiry->gender,

                    'class' =>
                        $enquiry->class,


                    /*
                    | Parent
                    */

                    'father_name' =>
                        $enquiry->father_name,

                    'mother_name' =>
                        $enquiry->mother_name,


                    /*
                    | Contact
                    */

                    'contact_no' =>
                        $enquiry->contact_no,

                    'email' =>
                        $enquiry->email,


                    /*
                    | School
                    */

                    'current_school' =>
                        $enquiry->current_school,


                    /*
                    | Address
                    */

                    'address' =>
                        $enquiry->address,

                    'pincode' =>
                        $enquiry->pincode,


                    /*
                    | Sibling
                    */

                    'sibling_currently_studying' =>
                        $enquiry->sibling_currently_studying,


                    /*
                    | Documents
                    */

                    'all_documents_available' =>
                        $enquiry->all_documents_available,


                    /*
                    | Question
                    */

                    'question' =>
                        $enquiry->question,

                    'created_at' =>
                        $enquiry->created_at,

                    'updated_at' =>
                        $enquiry->updated_at,
                ];
            }
        );


        return response()->json([

            'success' =>
                true,

            'data' => [

                'enquiries' =>
                    $enquiries,

            ]

        ]);
    }


    /**
     * ============================================================
     * GET SINGLE ADMISSION ENQUIRY
     * ============================================================
     *
     * GET:
     * /api/admission/enquiries/{id}
     *
     * nar_id is also checked here so a parent can only
     * access their own enquiry.
     */
    public function show(Request $request, $id)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate nar_id
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'nar_id' => [
                'required',
                'integer'
            ]
        ]);

        $narId = $validated['nar_id'];


        /*
        |--------------------------------------------------------------------------
        | Find Enquiry For This Parent Only
        |--------------------------------------------------------------------------
        */

        $enquiry = Enquiry::where(
            'id',
            $id
        )
        ->where(
            'nar_id',
            $narId
        )
        ->first();


        if (!$enquiry) {

            return response()->json([

                'success' =>
                    false,

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

            'M' =>
                'Male',

            'F' =>
                'Female',

            'O' =>
                'Other',
        ];


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                true,

            'data' => [

                'id' =>
                    $enquiry->id,

                'enquiry_number' =>
                    $enquiry->enquiry_number,


                /*
                | Student
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
                    $genderMap[
                        $enquiry->gender
                    ] ?? $enquiry->gender,

                'class' =>
                    $enquiry->class,


                /*
                | Parent
                */

                'father_name' =>
                    $enquiry->father_name,

                'mother_name' =>
                    $enquiry->mother_name,


                /*
                | Contact
                */

                'contact_no' =>
                    $enquiry->contact_no,

                'email' =>
                    $enquiry->email,


                /*
                | School
                */

                'current_school' =>
                    $enquiry->current_school,


                /*
                | Address
                */

                'address' =>
                    $enquiry->address,

                'pincode' =>
                    $enquiry->pincode,


                /*
                | Sibling
                */

                'sibling_currently_studying' =>
                    $enquiry->sibling_currently_studying,


                /*
                | Documents
                */

                'all_documents_available' =>
                    $enquiry->all_documents_available,


                /*
                | Question
                */

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