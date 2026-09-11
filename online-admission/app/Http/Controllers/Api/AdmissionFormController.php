<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdmissionForm;

class AdmissionFormController extends Controller
{
    /**
     * Get admission form details by class and academic year.
     *
     * Example:
     * GET /api/admission/form/150?academic_yr=2026-2027
     */
    public function getFormDetails(Request $request, $classId)
    {
        // Get academic year from query parameter
        $academicYear = $request->query('academic_yr');

        // Validate academic year
        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'academic_yr is required.'
            ], 422);
        }

        // Find the admission form using class ID + academic year
        $form = AdmissionForm::where('class_id', $classId)
            ->where('academic_yr', $academicYear)
            ->where('is_active', 'Y')
            ->first();

        // If form does not exist
        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'Admission form not found for this class and academic year.'
            ], 404);
        }

        // Return dynamic form details
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
}