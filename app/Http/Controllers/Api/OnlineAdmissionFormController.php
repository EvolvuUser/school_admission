<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OnlineAdmissionForm;
use Illuminate\Support\Facades\DB;

class AdmissionFormController extends Controller
{
    /**
     * Get admission form details by class and academic year.
     *
     * GET:
     * /api/admission/form/{classId}?academic_yr=2026-2027
     */
    public function getFormDetails(Request $request, $classId)
    {
        $academicYear = $request->query('academic_yr');

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'academic_yr is required.'
            ], 422);
        }

        $form = DB::table('admission_forms')
            ->where('class_id', $classId)
            ->where('academic_yr', $academicYear)
            ->where('is_active', 'Y')
            ->first();

        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'Admission form not found for this class and academic year.'
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
     */
    public function saveStudentDetails(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'form_id' => 'required',
            'academic_yr' => 'required|string',
            'class_id' => 'required',

            'first_name' => 'required|string|max:100',
            'mid_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',

            'dob' => 'required|date',

            'birth_place' => 'required|string|max:255',

            'gender' => 'required|string|max:20',

            'religion' => 'required|string|max:100',

            'caste' => 'nullable|string|max:100',

            'subcaste' => 'nullable|string|max:100',

            'nationality' => 'required|string|max:100',

            'mother_tongue' => 'required|string|max:100',

            'category' => 'required|string|max:100',

            /*
             * locality is required by the existing database table.
             */
            'locality' => 'required|string|max:255',
        ]);


        try {

            /*
            |--------------------------------------------------------------------------
            | Prepare Student Data
            |--------------------------------------------------------------------------
            |
            | This follows the old CodeIgniter implementation.
            |
            */

            $data = [

                'form_id' => $validated['form_id'],

                'academic_yr' => $validated['academic_yr'],

                'class_id' => $validated['class_id'],

                'first_name' =>
                    strtoupper(trim($validated['first_name'])),

                'mid_name' =>
                    isset($validated['mid_name'])
                        ? strtoupper(trim($validated['mid_name']))
                        : null,

                'last_name' =>
                    strtoupper(trim($validated['last_name'])),

                'dob' =>
                    $validated['dob'],

                'birth_place' =>
                    strtoupper(trim($validated['birth_place'])),

                'gender' =>
                    strtoupper(trim($validated['gender'])),

                'religion' =>
                    strtoupper(trim($validated['religion'])),

                'caste' =>
                    isset($validated['caste'])
                        ? strtoupper(trim($validated['caste']))
                        : null,

                'subcaste' =>
                    isset($validated['subcaste'])
                        ? strtoupper(trim($validated['subcaste']))
                        : null,

                'nationality' =>
                    strtoupper(trim($validated['nationality'])),

                'mother_tongue' =>
                    strtoupper(trim($validated['mother_tongue'])),

                'category' =>
                    strtoupper(trim($validated['category'])),

                /*
                 * Existing DB column is NOT NULL.
                 */
                'locality' =>
                    strtoupper(trim($validated['locality'])),

                /*
                 * Same as old CodeIgniter code.
                 */
                'application_date' => now(),

                /*
                 * Initial status.
                 */
                'admission_form_status' => 'Draft',
            ];


            /*
            |--------------------------------------------------------------------------
            | Save Into Existing Table
            |--------------------------------------------------------------------------
            |
            | online_admission_form does NOT have Laravel timestamps,
            | therefore we use DB::table() instead of Eloquent create().
            |
            */

            $applicationId = DB::table('online_admission_form')
                ->insertGetId($data);


            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,

                'message' => 'Student details saved successfully.',

                'data' => [
                    'adm_form_pk' => $applicationId,

                    'form_id' => $data['form_id'],

                    'academic_yr' => $data['academic_yr'],

                    'class_id' => $data['class_id'],

                    'first_name' => $data['first_name'],

                    'mid_name' => $data['mid_name'],

                    'last_name' => $data['last_name'],

                    'dob' => $data['dob'],

                    'birth_place' => $data['birth_place'],

                    'gender' => $data['gender'],

                    'religion' => $data['religion'],

                    'caste' => $data['caste'],

                    'subcaste' => $data['subcaste'],

                    'nationality' => $data['nationality'],

                    'mother_tongue' => $data['mother_tongue'],

                    'category' => $data['category'],

                    'locality' => $data['locality'],

                    'admission_form_status' =>
                        $data['admission_form_status'],
                ]
            ], 201);


        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | Database Error
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => false,

                'message' => 'Failed to save student details.',

                'error' => $e->getMessage()
            ], 500);
        }
    }
}