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
     */
    public function store(Request $request)
    {
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
            'last_name',
            'father_name',
            'mother_name',
            'contact_no',
            'current_school',
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
        | The API can accept:
        |
        | true
        | false
        |
        | "Y"
        | "N"
        |
        | 1
        | 0
        |
        | "yes"
        | "no"
        |
        | Internally we ALWAYS convert it to:
        |
        | Y
        | N
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
            | Documents
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | Database stores:
            |
            | Y
            | N
            |
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
        |
        | Frontend sends:
        |
        | "class": "UKG"
        |
        */

        $className = trim(
            $validated['class']
        );

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
        |
        | Frontend:
        |
        | 06/02/2002
        |
        | MM/DD/YYYY
        |
        | Database:
        |
        | 2002-06-02
        |
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
        |
        | ALWAYS store:
        |
        | Y
        | N
        |
        */

        $documentsAvailable =
            $validated['all_documents_available'] ?? 'N';


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
            |
            | Save class NAME.
            |
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
            | Documents
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | This is now CHAR(1) in database.
            |
            | Only Y or N will be stored.
            |
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
                    $enquiry->dob,

                /*
                 * Return frontend gender.
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

                /*
                 * Return Y/N.
                 */

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
     * ============================================================
     * GET ALL ADMISSION ENQUIRIES
     * ============================================================
     *
     * GET:
     * /api/admission/enquiries
     */
    public function index()
    {
        $enquiries =
            Enquiry::orderByDesc('id')
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
                     * Class name.
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

                    /*
                     * IMPORTANT:
                     *
                     * Return Y/N directly.
                     */

                    'all_documents_available' =>
                        $enquiry->all_documents_available,

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

            'success' => true,

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
     */
    public function show($id)
    {
        $enquiry =
            Enquiry::find($id);


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

                'enquiry_number' =>
                    $enquiry->enquiry_number,

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

                /*
                 * IMPORTANT:
                 *
                 * Return Y/N directly.
                 */

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