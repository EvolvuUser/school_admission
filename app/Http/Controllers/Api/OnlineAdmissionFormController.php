<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OnlineAdmissionForm;

class OnlineAdmissionFormController extends Controller
{
    /**
     * Get a saved online admission form by ID.
     *
     * GET:
     * /api/admission/online-form/{id}
     */
    public function show($id)
    {
        $student = OnlineAdmissionForm::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Online admission form not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }


    /**
     * Get all online admission forms.
     *
     * GET:
     * /api/admission/online-forms
     */
    public function index()
    {
        $students = OnlineAdmissionForm::orderBy(
            'adm_form_pk',
            'desc'
        )->get();

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }


    /**
     * Update student details.
     *
     * PUT:
     * /api/admission/online-form/{id}
     */
    public function update(Request $request, $id)
    {
        /*
        |--------------------------------------------------------------------------
        | Find Existing Application
        |--------------------------------------------------------------------------
        */

        $student = OnlineAdmissionForm::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Online admission form not found.'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            // Student details

            'first_name' => 'sometimes|required|string|max:100',

            'mid_name' => 'nullable|string|max:100',

            'last_name' => 'sometimes|required|string|max:100',

            'dob' => 'sometimes|required|date',

            'birth_place' => 'sometimes|required|string|max:50',

            'gender' => 'sometimes|required|string|max:1',

            'religion' => 'sometimes|required|string|max:100',

            'caste' => 'nullable|string|max:100',

            'subcaste' => 'nullable|string|max:100',

            'nationality' => 'sometimes|required|string|max:100',

            'mother_tongue' => 'sometimes|required|string|max:20',

            'category' => 'sometimes|required|string|max:8',


            // Address details

            'locality' => 'sometimes|required|string|max:50',

            'city' => 'sometimes|required|string|max:30',

            'state' => 'sometimes|required|string|max:30',

            'pincode' => 'sometimes|required|integer',

            'perm_address' => 'sometimes|required|string|max:100',


            // Sibling details

            'sibling' => 'sometimes|required|string|size:1',

            'sibling_class_id' => 'nullable|string|max:10',

            'sibling_student_id' => 'nullable|string|max:100',


            // Father details

            'father_name' => 'nullable|string|max:100',

            'father_occupation' => 'nullable|string|max:100',

            'f_mobile' => 'nullable|string|max:10',

            'f_email' => 'nullable|email|max:50',

            'f_qualification' => 'nullable|string|max:50',

            'f_designation' => 'nullable|string|max:50',

            'f_nature_of_bussiness' => 'nullable|string|max:100',

            'f_office_add' => 'nullable|string|max:100',

            'f_aadhar_no' => 'nullable|string|max:14',


            // Mother details

            'mother_name' => 'nullable|string|max:100',

            'mother_occupation' => 'nullable|string|max:100',

            'm_mobile' => 'nullable|string|max:13',

            'm_emailid' => 'nullable|email|max:50',

            'm_qualification' => 'nullable|string|max:50',

            'm_designation' => 'nullable|string|max:50',

            'm_nature_of_bussiness' => 'nullable|string|max:100',

            'm_office_add' => 'nullable|string|max:100',

            'm_aadhar_no' => 'nullable|string|max:14',


            // Additional details

            'stud_aadhar' => 'nullable|string|max:14',

            'blood_group' => 'nullable|string|max:5',

            'current_school_class' => 'nullable|string|max:100',

            'acheivements' => 'nullable|string|max:100',


            // Parent contribution

            'area_in_which_parent_can_contribute'
                => 'nullable|string|max:100',

            'other_area'
                => 'nullable|string|max:50',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Convert Text Fields To Uppercase
        |--------------------------------------------------------------------------
        */

        $uppercaseFields = [

            'first_name',
            'mid_name',
            'last_name',
            'birth_place',
            'gender',
            'religion',
            'caste',
            'subcaste',
            'nationality',
            'mother_tongue',
            'category',

            'locality',
            'city',
            'state',
            'perm_address',

            'sibling',

            'father_name',
            'father_occupation',
            'f_mobile',
            'f_qualification',
            'f_designation',
            'f_nature_of_bussiness',
            'f_office_add',

            'mother_name',
            'mother_occupation',
            'm_mobile',
            'm_qualification',
            'm_designation',
            'm_nature_of_bussiness',
            'm_office_add',

            'blood_group',
            'current_school_class',
            'acheivements',

            'area_in_which_parent_can_contribute',
            'other_area',
        ];


        foreach ($validated as $key => $value) {

            if (
                is_string($value) &&
                in_array($key, $uppercaseFields)
            ) {
                $validated[$key] = strtoupper(trim($value));
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Update Student Details
        |--------------------------------------------------------------------------
        |
        | We intentionally do NOT update:
        |
        | form_id
        | academic_yr
        | class_id
        | nar_id
        |
        | These values belong to the original application.
        |
        */

        $student->update($validated);


        /*
        |--------------------------------------------------------------------------
        | Set Application Status
        |--------------------------------------------------------------------------
        */

        $student->admission_form_status = 'Applied';

        $student->save();


        /*
        |--------------------------------------------------------------------------
        | Success Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' => 'Student details updated successfully.',

            'data' => [
                'adm_form_pk' =>
                    $student->adm_form_pk,

                'nar_id' =>
                    $student->nar_id,

                'form_id' =>
                    $student->form_id,

                'academic_yr' =>
                    $student->academic_yr,

                'class_id' =>
                    $student->class_id,


                // Student details

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


                // Address details

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


                // Sibling details

                'sibling' =>
                    $student->sibling,

                'sibling_class_id' =>
                    $student->sibling_class_id,

                'sibling_student_id' =>
                    $student->sibling_student_id,


                // Father details

                'father_name' =>
                    $student->father_name,

                'father_occupation' =>
                    $student->father_occupation,

                'f_mobile' =>
                    $student->f_mobile,

                'f_email' =>
                    $student->f_email,

                'f_qualification' =>
                    $student->f_qualification,

                'f_designation' =>
                    $student->f_designation,

                'f_nature_of_bussiness' =>
                    $student->f_nature_of_bussiness,

                'f_office_add' =>
                    $student->f_office_add,

                'f_aadhar_no' =>
                    $student->f_aadhar_no,


                // Mother details

                'mother_name' =>
                    $student->mother_name,

                'mother_occupation' =>
                    $student->mother_occupation,

                'm_mobile' =>
                    $student->m_mobile,

                'm_emailid' =>
                    $student->m_emailid,

                'm_qualification' =>
                    $student->m_qualification,

                'm_designation' =>
                    $student->m_designation,

                'm_nature_of_bussiness' =>
                    $student->m_nature_of_bussiness,

                'm_office_add' =>
                    $student->m_office_add,

                'm_aadhar_no' =>
                    $student->m_aadhar_no,


                // Additional details

                'stud_aadhar' =>
                    $student->stud_aadhar,

                'blood_group' =>
                    $student->blood_group,

                'current_school_class' =>
                    $student->current_school_class,

                'acheivements' =>
                    $student->acheivements,


                // Parent contribution

                'area_in_which_parent_can_contribute' =>
                    $student->area_in_which_parent_can_contribute,

                'other_area' =>
                    $student->other_area,


                // Status

                'admission_form_status' =>
                    $student->admission_form_status,
            ]
        ], 200);
    }


    /**
     * Delete online admission form.
     *
     * DELETE:
     * /api/admission/online-form/{id}
     */
    public function destroy($id)
    {
        $student = OnlineAdmissionForm::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Online admission form not found.'
            ], 404);
        }

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Online admission form deleted successfully.'
        ]);
    }
}
