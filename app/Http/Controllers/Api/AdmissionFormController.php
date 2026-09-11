<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdmissionForm;
use App\Models\OnlineAdmissionForm;
use Illuminate\Support\Facades\DB;

class AdmissionFormController extends Controller
{
    /**
     * Get admission form details by form ID and academic year.
     *
     * GET:
     * /api/admission/form/{formId}?academic_yr=2026-2027
     */
    public function getFormDetails(Request $request, $formId)
    {
        $academicYear = $request->query('academic_yr');

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'academic_yr is required.'
            ], 422);
        }

        $form = AdmissionForm::where('form_id', $formId)
            ->where('academic_yr', $academicYear)
            ->where('is_active', 'Y')
            ->first();

        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'Admission form not found for this form ID and academic year.'
            ], 404);
        }

        return response()->json([
            'success' => true,

            'data' => [
                'form_id' => $form->form_id,

                'class_id' => $form->class_id,

                'academic_yr' => $form->academic_yr,

                'form_title' => $form->form_title,

                'form_description' => $form->form_description,

                'form_configuration' => [
                    'student_details' =>
                        $form->student_details_enabled === 'Y',

                    'address_details' =>
                        $form->address_details_enabled === 'Y',

                    'additional_details' =>
                        $form->additional_details_enabled === 'Y',
                ]
            ]
        ]);
    }


    /**
     * Save Student Details
     *
     * POST:
     * /api/admission/student-details
     *
     * The user sends:
     * - class_id
     * - academic_yr
     * - student details
     * - address details
     *
     * form_id is generated automatically.
     */
    public function saveStudentDetails(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            /*
             * Selected class.
             */
            'class_id' => 'required|integer',

            /*
             * Requested academic year.
             */
            'academic_yr' => 'required|string|max:11',

            /*
             * Registration ID.
             */
            'nar_id' => 'required|integer',

            /*
             * Student details.
             */
            'first_name' => 'required|string|max:100',

            'mid_name' => 'nullable|string|max:100',

            'last_name' => 'nullable|string|max:100',

            'dob' => 'required|date',

            'birth_place' => 'required|string|max:50',

            'gender' => 'required|string|max:1',

            'religion' => 'nullable|string|max:100',

            'caste' => 'nullable|string|max:100',

            'subcaste' => 'nullable|string|max:100',

            'nationality' => 'nullable|string|max:100',

            'mother_tongue' => 'required|string|max:20',

            'category' => 'required|string|max:8',

            /*
             * Address details.
             */
            'locality' => 'required|string|max:50',

            'city' => 'required|string|max:30',

            'state' => 'required|string|max:30',

            'pincode' => 'required|integer',

            'perm_address' => 'required|string|max:100',

            /*
             * Sibling details.
             *
             * Database requires sibling.
             */
            'sibling' => 'required|string|size:1',

            'sibling_class_id' => 'nullable|string|max:10',

            'sibling_student_id' => 'nullable|string|max:100',

            /*
             * Optional phone number.
             */
            'sms_sending_phone_no' => 'nullable|string|max:10',

            /*
             * Optional parent area.
             */
            'other_area' => 'nullable|string|max:50',
        ]);


        try {

            DB::beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Find Selected Class
            |--------------------------------------------------------------------------
            |
            | class_id comes from the selected class.
            | We fetch the actual class name from database.
            |
            */

            $class = DB::table('class')
                ->where('class_id', $validated['class_id'])
                ->first();


            if (!$class) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Selected class not found.'
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | Find Admission Form
            |--------------------------------------------------------------------------
            |
            | We use BOTH:
            |
            | class_id
            | academic_yr
            |
            | Therefore the requested academic year is used consistently.
            |
            */

            $admissionForm = AdmissionForm::where(
                    'class_id',
                    $validated['class_id']
                )
                ->where(
                    'academic_yr',
                    $validated['academic_yr']
                )
                ->where('is_active', 'Y')
                ->first();


            if (!$admissionForm) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' =>
                        'No active admission form found for the selected class and academic year.'
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | Academic Year
            |--------------------------------------------------------------------------
            |
            | Take the academic year from the matched database record.
            |
            */

            $academicYear = $admissionForm->academic_yr;


            /*
            |--------------------------------------------------------------------------
            | Prepare Academic Year For Form Number
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | 2026-2027
            |
            | becomes:
            |
            | 2026-27
            |
            */

            $shortAcademicYear = $academicYear;

            if (preg_match('/^(\d{4})-(\d{4})$/', $academicYear, $matches)) {

                $shortAcademicYear =
                    $matches[1] . '-' . substr($matches[2], -2);
            }


            /*
            |--------------------------------------------------------------------------
            | Get Class Name
            |--------------------------------------------------------------------------
            */

            $className = $class->name;


            /*
            |--------------------------------------------------------------------------
            | Clean Class Name
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | "Grade 1"
            |
            | becomes:
            |
            | "GRADE-1"
            |
            */

            $className = strtoupper(trim($className));

            $className = preg_replace('/[^A-Z0-9]+/', '-', $className);

            $className = trim($className, '-');


            /*
            |--------------------------------------------------------------------------
            | Generate Unique Form ID
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | 2026-27-GRADE-1-0001
            |
            | 2026-27-GRADE-1-0002
            |
            | 2026-27-GRADE-1-0003
            |
            */

            $prefix = $shortAcademicYear . '-' . $className;


            /*
             * Get the latest form ID starting with this prefix.
             */
            $lastStudent = OnlineAdmissionForm::where(
                    'form_id',
                    'like',
                    $prefix . '-%'
                )
                ->orderByDesc('adm_form_pk')
                ->first();


            $nextNumber = 1;


            if ($lastStudent && $lastStudent->form_id) {

                /*
                 * Get the last numeric part.
                 *
                 * Example:
                 *
                 * 2026-27-GRADE-1-0007
                 *
                 * gives:
                 *
                 * 7
                 */
                $parts = explode('-', $lastStudent->form_id);

                $lastNumber = end($parts);

                if (is_numeric($lastNumber)) {

                    $nextNumber = ((int) $lastNumber) + 1;
                }
            }


            /*
             * Four digit sequence.
             */
            $uniqueFormId =
                $prefix . '-' . str_pad(
                    $nextNumber,
                    4,
                    '0',
                    STR_PAD_LEFT
                );


            /*
            |--------------------------------------------------------------------------
            | Safety Check
            |--------------------------------------------------------------------------
            |
            | Because form_id is UNIQUE in the database,
            | check again before inserting.
            |
            */

            while (
                OnlineAdmissionForm::where(
                    'form_id',
                    $uniqueFormId
                )->exists()
            ) {

                $nextNumber++;

                $uniqueFormId =
                    $prefix . '-' . str_pad(
                        $nextNumber,
                        4,
                        '0',
                        STR_PAD_LEFT
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Prepare Student Data
            |--------------------------------------------------------------------------
            */

            $data = [

                /*
                 * Automatically generated application form ID.
                 */
                'form_id' => $uniqueFormId,

                /*
                 * Academic year from admission_forms.
                 */
                'academic_yr' => $academicYear,

                /*
                 * Selected class ID.
                 */
                'class_id' => $validated['class_id'],


                /*
                 * Student details.
                 */
                'first_name' =>
                    strtoupper(trim($validated['first_name'])),

                'mid_name' =>
                    isset($validated['mid_name'])
                        ? strtoupper(trim($validated['mid_name']))
                        : null,

                'last_name' =>
                    isset($validated['last_name'])
                        ? strtoupper(trim($validated['last_name']))
                        : null,

                'dob' => $validated['dob'],

                'birth_place' =>
                    strtoupper(trim($validated['birth_place'])),

                'gender' =>
                    strtoupper(trim($validated['gender'])),

                'religion' =>
                    isset($validated['religion'])
                        ? strtoupper(trim($validated['religion']))
                        : null,

                'caste' =>
                    isset($validated['caste'])
                        ? strtoupper(trim($validated['caste']))
                        : null,

                'subcaste' =>
                    isset($validated['subcaste'])
                        ? strtoupper(trim($validated['subcaste']))
                        : null,

                'nationality' =>
                    isset($validated['nationality'])
                        ? strtoupper(trim($validated['nationality']))
                        : null,

                'mother_tongue' =>
                    strtoupper(trim($validated['mother_tongue'])),

                'category' =>
                    strtoupper(trim($validated['category'])),


                /*
                 * Address details.
                 */
                'locality' =>
                    strtoupper(trim($validated['locality'])),

                'city' =>
                    strtoupper(trim($validated['city'])),

                'state' =>
                    strtoupper(trim($validated['state'])),

                'pincode' =>
                    $validated['pincode'],

                'perm_address' =>
                    strtoupper(trim($validated['perm_address'])),


                /*
                 * Sibling details.
                 */
                'sibling' =>
                    strtoupper(trim($validated['sibling'])),

                'sibling_class_id' =>
                    $validated['sibling_class_id'] ?? null,

                'sibling_student_id' =>
                    $validated['sibling_student_id'] ?? null,


                /*
                 * Registration ID.
                 */
                'nar_id' => $validated['nar_id'],


                /*
                 * New application.
                 */
                'student_id' => 0,


                /*
                 * Database requires other_area.
                 */
                'other_area' =>
                    isset($validated['other_area'])
                        ? strtoupper(trim($validated['other_area']))
                        : '',


                /*
                 * Database requires sms_sending_phone_no.
                 */
                'sms_sending_phone_no' =>
                    $validated['sms_sending_phone_no'] ?? '',


                /*
                 * Application date.
                 */
                'application_date' => now()->toDateString(),


                /*
                 * Final application status.
                 */
                'admission_form_status' => 'Applied',
            ];


            /*
            |--------------------------------------------------------------------------
            | Save Student Admission Form
            |--------------------------------------------------------------------------
            */

            $student = OnlineAdmissionForm::create($data);


            DB::commit();


            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' => true,

                'message' =>
                    'Student admission form submitted successfully.',

                'data' => [

                    /*
                     * Generated application ID.
                     */
                    'form_id' =>
                        $student->form_id,

                    /*
                     * Registration ID.
                     */
                    'nar_id' =>
                        $student->nar_id,

                    /*
                     * Academic year.
                     */
                    'academic_yr' =>
                        $student->academic_yr,

                    /*
                     * Selected class.
                     */
                    'class_id' =>
                        $student->class_id,

                    /*
                     * Class name.
                     */
                    'class_name' =>
                        $class->name,

                    /*
                     * Database primary key.
                     */
                    'adm_form_pk' =>
                        $student->adm_form_pk,


                    /*
                     * Student details.
                     */
                    'first_name' =>
                        $student->first_name,

                    'mid_name' =>
                        $student->mid_name,

                    'last_name' =>
                        $student->last_name,

                    'dob' =>
                        $student->dob,

                    'birth_place' =>
                        $student->birth_place,

                    'gender' =>
                        $student->gender,

                    'religion' =>
                        $student->religion,

                    'caste' =>
                        $student->caste,

                    'subcaste' =>
                        $student->subcaste,

                    'nationality' =>
                        $student->nationality,

                    'mother_tongue' =>
                        $student->mother_tongue,

                    'category' =>
                        $student->category,


                    /*
                     * Address.
                     */
                    'locality' =>
                        $student->locality,

                    'city' =>
                        $student->city,

                    'state' =>
                        $student->state,

                    'pincode' =>
                        $student->pincode,

                    'perm_address' =>
                        $student->perm_address,


                    /*
                     * Sibling.
                     */
                    'sibling' =>
                        $student->sibling,

                    'sibling_class_id' =>
                        $student->sibling_class_id,

                    'sibling_student_id' =>
                        $student->sibling_student_id,


                    /*
                     * Application status.
                     */
                    'admission_form_status' =>
                        $student->admission_form_status,
                ]

            ], 201);


        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | Rollback
            |--------------------------------------------------------------------------
            */

            DB::rollBack();


            /*
            |--------------------------------------------------------------------------
            | Database Error
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' => false,

                'message' =>
                    'Failed to save student details.',

                'error' =>
                    $e->getMessage()

            ], 500);
        }
    }
}
