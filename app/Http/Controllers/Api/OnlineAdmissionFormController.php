<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdmissionUploadDocument;
use App\Models\OnlineAdmissionForm;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class OnlineAdmissionFormController extends Controller
{
    /**
     * Get a saved online admission form.
     *
     * GET:
     * /api/admission/online-form/{formId}?nar_id=2856
     */
    public function show(Request $request, $formId)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate nar_id
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'nar_id' => 'required|integer',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Find Admission Form
        |--------------------------------------------------------------------------
        */

        $student = OnlineAdmissionForm::where(
            'form_id',
            $formId
        )->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Online admission form not found.'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Ownership Check
        |--------------------------------------------------------------------------
        */

        if (
            (int) $student->nar_id !==
            (int) $validated['nar_id']
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to access this admission form.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Get Uploaded Documents
        |--------------------------------------------------------------------------
        */

        $documents = AdmissionUploadDocument::where(
            'form_id',
            $student->form_id
        )
            ->get()
            ->map(function ($document) {

                $document->document_url = asset(
                    'storage/admission_documents/' .
                    $document->image_name
                );

                return $document;
            });

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'data' => [
                'application' => $student,
                'documents' => $documents,
            ],

            'form_id' => $student->form_id,
            'nar_id' => $student->nar_id,
        ]);
    }


    /**
     * Get all online admission forms for a nar_id.
     *
     * GET:
     * /api/admission/online-forms?nar_id=2856
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate nar_id
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'nar_id' => 'required|integer',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Get Forms
        |--------------------------------------------------------------------------
        */

        $students = OnlineAdmissionForm::where(
            'nar_id',
            $validated['nar_id']
        )
            ->orderByDesc('adm_form_pk')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }


    /**
     * Download a saved online admission form as PDF.
     *
     * GET:
     * /api/admission/online-form/{formId}/download?nar_id=2856
     *
     * PDF Template:
     * resources/views/admission/online-form-pdf.blade.php
     */
    public function download(Request $request, $formId)
    {
        /*
        |--------------------------------------------------------------------------
        | Get nar_id
        |--------------------------------------------------------------------------
        |
        | Supports:
        |
        | ?nar_id=2856
        |
        | JSON:
        | {
        |     "nar_id": 2856
        | }
        |
        | Also supports narId for frontend compatibility.
        |
        */

        $request->merge([
            'nar_id' => $request->query(
                'nar_id',
                $request->input(
                    'nar_id',
                    $request->input('narId')
                )
            ),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validate nar_id
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'nar_id' => 'required|integer',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Find Admission Form
        |--------------------------------------------------------------------------
        */

        $student = OnlineAdmissionForm::where(
            'form_id',
            $formId
        )->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Online admission form not found.'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Ownership Check
        |--------------------------------------------------------------------------
        |
        | The form can only be downloaded by the nar_id
        | to which the form belongs.
        |
        */

        if (
            (int) $student->nar_id !==
            (int) $validated['nar_id']
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to download this admission form.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Get Uploaded Documents
        |--------------------------------------------------------------------------
        */

        $documents = AdmissionUploadDocument::where(
            'form_id',
            $student->form_id
        )
            ->orderBy('doc_type')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Get Class Name
        |--------------------------------------------------------------------------
        |
        | online_admission_form stores class_id.
        |
        | The PDF should display:
        |
        |     UKG
        |
        | instead of:
        |
        |     150
        |
        */

        $class = DB::table('class')
            ->where(
                'class_id',
                $student->class_id
            )
            ->first();

        $className = $class
            ? $class->name
            : $student->class_id;

        /*
        |--------------------------------------------------------------------------
        | Convert Application Status
        |--------------------------------------------------------------------------
        |
        | Do not display raw database codes such as:
        |
        |     S
        |
        | Display:
        |
        |     Applied
        |
        */

        $status = $student->admission_form_status;

        if (!$status) {
            $status = $student->status;
        }

        $statusMap = [
            'S' => 'Applied',
            'A' => 'Applied',
            'Applied' => 'Applied',

            'D' => 'Draft',
            'Draft' => 'Draft',

            'C' => 'Cancelled',
            'Cancelled' => 'Cancelled',

            'Approved' => 'Approved',
            'Rejected' => 'Rejected',
        ];

        $displayStatus = $statusMap[$status]
            ?? $status
            ?? 'N/A';

        /*
        |--------------------------------------------------------------------------
        | School Logo
        |--------------------------------------------------------------------------
        |
        | Your logo is inside:
        |
        | public/images/school/
        |
        | Based on the file currently shown in VS Code:
        |
        | logo.jpg.jpeg
        |
        */

        $logoPath = public_path(
            'images/school/logo.jpg.jpeg'
        );

        /*
        |--------------------------------------------------------------------------
        | Check Logo Exists
        |--------------------------------------------------------------------------
        */

        if (!file_exists($logoPath)) {
            $logoPath = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Generate PDF From Blade Template
        |--------------------------------------------------------------------------
        |
        | Blade:
        |
        | resources/views/admission/online-form-pdf.blade.php
        |
        | DomPDF converts the HTML/CSS into a real PDF.
        |
        */

        $pdf = Pdf::loadView(
            'admission.online-form-pdf',
            [
                'student' => $student,

                'documents' => $documents,

                'className' => $className,

                'displayStatus' => $displayStatus,

                'logoPath' => $logoPath,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | A4 Portrait
        |--------------------------------------------------------------------------
        */

        $pdf->setPaper(
            'a4',
            'portrait'
        );

        /*
        |--------------------------------------------------------------------------
        | Download PDF
        |--------------------------------------------------------------------------
        */

        return $pdf->download(
            $student->form_id .
            '-admission-form.pdf'
        );
    }


    /**
     * Update student details.
     *
     * PUT:
     * /api/admission/online-form/{formId}?nar_id=2856
     */
    public function update(Request $request, $formId)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Ownership
            |--------------------------------------------------------------------------
            */

            'nar_id' => 'required|integer',

            /*
            |--------------------------------------------------------------------------
            | Student Details
            |--------------------------------------------------------------------------
            */

            'first_name' =>
                'sometimes|required|string|max:100',

            'mid_name' =>
                'nullable|string|max:100',

            'last_name' =>
                'sometimes|required|string|max:100',

            'dob' =>
                'sometimes|required|date',

            'birth_place' =>
                'sometimes|required|string|max:50',

            'gender' =>
                'sometimes|required|string|max:20',

            'religion' =>
                'sometimes|required|string|max:100',

            'caste' =>
                'nullable|string|max:100',

            'subcaste' =>
                'nullable|string|max:100',

            'nationality' =>
                'sometimes|required|string|max:100',

            'mother_tongue' =>
                'sometimes|required|string|max:20',

            'category' =>
                'sometimes|required|string|max:8',

            /*
            |--------------------------------------------------------------------------
            | Address Details
            |--------------------------------------------------------------------------
            */

            'locality' =>
                'sometimes|required|string|max:50',

            'city' =>
                'sometimes|required|string|max:30',

            'state' =>
                'sometimes|required|string|max:30',

            'pincode' =>
                'sometimes|required|integer',

            'perm_address' =>
                'sometimes|required|string|max:100',

            /*
            |--------------------------------------------------------------------------
            | Sibling Details
            |--------------------------------------------------------------------------
            */

            'sibling' =>
                'sometimes|required|string|max:20',

            'sibling_class_id' =>
                'nullable|string|max:10',

            'sibling_student_id' =>
                'nullable|string|max:100',

            /*
            |--------------------------------------------------------------------------
            | Father Details
            |--------------------------------------------------------------------------
            */

            'father_name' =>
                'nullable|string|max:100',

            'father_occupation' =>
                'nullable|string|max:100',

            'f_mobile' =>
                'nullable|string|max:10',

            'f_email' =>
                'nullable|email|max:50',

            'f_qualification' =>
                'nullable|string|max:50',

            'f_designation' =>
                'nullable|string|max:50',

            'f_nature_of_bussiness' =>
                'nullable|string|max:100',

            'f_office_add' =>
                'nullable|string|max:100',

            'f_aadhar_no' =>
                'nullable|string|max:14',

            /*
            |--------------------------------------------------------------------------
            | Mother Details
            |--------------------------------------------------------------------------
            */

            'mother_name' =>
                'nullable|string|max:100',

            'mother_occupation' =>
                'nullable|string|max:100',

            'm_mobile' =>
                'nullable|string|max:13',

            'm_emailid' =>
                'nullable|email|max:50',

            'm_qualification' =>
                'nullable|string|max:50',

            'm_designation' =>
                'nullable|string|max:50',

            'm_nature_of_bussiness' =>
                'nullable|string|max:100',

            'm_office_add' =>
                'nullable|string|max:100',

            'm_aadhar_no' =>
                'nullable|string|max:14',

            /*
            |--------------------------------------------------------------------------
            | Additional Details
            |--------------------------------------------------------------------------
            */

            'stud_aadhar' =>
                'nullable|string|max:14',

            'blood_group' =>
                'nullable|string|max:5',

            'current_school_class' =>
                'nullable|string|max:100',

            'acheivements' =>
                'nullable|string|max:100',

            /*
            |--------------------------------------------------------------------------
            | Parent Contribution
            |--------------------------------------------------------------------------
            */

            'area_in_which_parent_can_contribute' =>
                'nullable|string|max:100',

            'other_area' =>
                'nullable|string|max:50',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Convert Gender
        |--------------------------------------------------------------------------
        */

        if (isset($validated['gender'])) {

            $validated['gender'] = match (
                strtolower(trim($validated['gender']))
            ) {

                'male' => 'M',

                'female' => 'F',

                'other' => 'O',

                default => strtoupper(
                    substr(
                        trim($validated['gender']),
                        0,
                        1
                    )
                ),
            };
        }

        /*
        |--------------------------------------------------------------------------
        | Convert Sibling
        |--------------------------------------------------------------------------
        */

        if (isset($validated['sibling'])) {

            $validated['sibling'] = match (
                strtolower(trim($validated['sibling']))
            ) {

                'yes' => 'Y',

                'no' => 'N',

                default => strtoupper(
                    substr(
                        trim($validated['sibling']),
                        0,
                        1
                    )
                ),
            };
        }

        /*
        |--------------------------------------------------------------------------
        | Find Existing Application
        |--------------------------------------------------------------------------
        */

        $student = OnlineAdmissionForm::where(
            'form_id',
            $formId
        )->first();

        if (!$student) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Online admission form not found.'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Ownership Check
        |--------------------------------------------------------------------------
        */

        if (
            (int) $student->nar_id !==
            (int) $validated['nar_id']
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to update this admission form.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Do Not Allow nar_id To Be Changed
        |--------------------------------------------------------------------------
        */

        unset($validated['nar_id']);

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

                $validated[$key] =
                    strtoupper(trim($value));
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Update Student Details
        |--------------------------------------------------------------------------
        |
        | These values are intentionally NOT changed:
        |
        | form_id
        | academic_yr
        | class_id
        | nar_id
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

            'message' =>
                'Student details updated successfully.',

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

                'sibling' =>
                    $student->sibling,

                'sibling_class_id' =>
                    $student->sibling_class_id,

                'sibling_student_id' =>
                    $student->sibling_student_id,

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

                'stud_aadhar' =>
                    $student->stud_aadhar,

                'blood_group' =>
                    $student->blood_group,

                'current_school_class' =>
                    $student->current_school_class,

                'acheivements' =>
                    $student->acheivements,

                'area_in_which_parent_can_contribute' =>
                    $student->area_in_which_parent_can_contribute,

                'other_area' =>
                    $student->other_area,

                'admission_form_status' =>
                    $student->admission_form_status,
            ]
        ], 200);
    }


    /**
     * Delete online admission form.
     *
     * DELETE:
     * /api/admission/online-form/{formId}?nar_id=2856
     */
    public function destroy(Request $request, $formId)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate nar_id
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'nar_id' => 'required|integer',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Find Admission Form
        |--------------------------------------------------------------------------
        */

        $student = OnlineAdmissionForm::where(
            'form_id',
            $formId
        )->first();

        if (!$student) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Online admission form not found.'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Ownership Check
        |--------------------------------------------------------------------------
        */

        if (
            (int) $student->nar_id !==
            (int) $validated['nar_id']
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to delete this admission form.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $student->delete();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' =>
                'Online admission form deleted successfully.'
        ]);
    }
}