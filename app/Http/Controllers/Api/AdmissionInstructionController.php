<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdmissionInstruction;

class AdmissionInstructionController extends Controller
{
    /**
     * Get admission instructions for a particular class and academic year.
     */
    public function getInstructions(Request $request, $classId)
    {
        // Get academic year
        $academicYear = $request->query('academic_yr');

        // Validate academic year
        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'academic_yr is required.'
            ], 422);
        }

        // Get admission instruction with all related sections
        $instruction = AdmissionInstruction::with([

            // Instructions
            'items' => function ($query) {
                $query->where('is_active', 'Y')
                    ->orderBy('display_order');
            },

            // Important dates
            'dates' => function ($query) {
                $query->where('is_active', 'Y')
                    ->orderBy('display_order');
            },

            // Required documents
            'documents' => function ($query) {
                $query->where('is_active', 'Y')
                    ->orderBy('display_order');
            },

            // Installments
            'installments' => function ($query) {
                $query->where('is_active', 'Y')
                    ->orderBy('display_order');
            },

            // Notes
            'notes' => function ($query) {
                $query->where('is_active', 'Y')
                    ->orderBy('display_order');
            },

            // Age criteria
            'ageCriteria' => function ($query) {
                $query->where('is_active', 'Y')
                    ->orderBy('display_order');
            }

        ])
        ->where('class_id', $classId)
        ->where('academic_yr', $academicYear)
        ->where('is_active', 'Y')
        ->first();

        // If instruction page doesn't exist
        if (!$instruction) {
            return response()->json([
                'success' => false,
                'message' => 'Admission instructions not found for this class and academic year.'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Total Annual Fee
        |--------------------------------------------------------------------------
        */

        $totalAnnualFee = 0;

        if ($instruction->installments) {

            foreach ($instruction->installments as $installment) {

                $totalAnnualFee += (float) ($installment->first_installment ?? 0);
                $totalAnnualFee += (float) ($installment->second_installment ?? 0);
                $totalAnnualFee += (float) ($installment->third_installment ?? 0);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Prepare Instructions
        |--------------------------------------------------------------------------
        */

        $instructions = $instruction->items->map(function ($item) {
            return [
                'instruction_item_id' => $item->instruction_item_id,
                'instruction' => $item->instruction,
                'display_order' => $item->display_order,
            ];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | Prepare Important Dates
        |--------------------------------------------------------------------------
        */

        $importantDates = $instruction->dates->map(function ($date) {
            return [
                'instruction_date_id' => $date->instruction_date_id,
                'event_date' => $date->event_date,
                'event_time' => $date->event_time,
                'description' => $date->description,
                'display_order' => $date->display_order,
            ];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | Prepare Required Documents
        |--------------------------------------------------------------------------
        */

        $requiredDocuments = $instruction->documents->map(function ($document) {
            return [
                'instruction_document_id' => $document->instruction_document_id,
                'document_title' => $document->document_title,
                'description' => $document->description,
                'display_order' => $document->display_order,
            ];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | Prepare Age Criteria
        |--------------------------------------------------------------------------
        */

        $ageCriteria = $instruction->ageCriteria->map(function ($criteria) {
            return [
                'age_criteria_id' => $criteria->age_criteria_id,
                'class_id' => $criteria->class_id,
                'minimum_age' => $criteria->minimum_age,
                'maximum_age' => $criteria->maximum_age,
                'cutoff_date' => $criteria->cutoff_date,
                'description' => $criteria->description,
                'display_order' => $criteria->display_order,
            ];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | Prepare Installments
        |--------------------------------------------------------------------------
        */

        $installments = $instruction->installments->map(function ($installment) {
            return [
                'instruction_installment_id' => $installment->instruction_installment_id,

                'first_installment' => $installment->first_installment,
                'second_installment' => $installment->second_installment,
                'third_installment' => $installment->third_installment,

                'payment_instructions' => $installment->payment_instructions,

                'display_order' => $installment->display_order,
            ];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | Prepare Notes
        |--------------------------------------------------------------------------
        */

        $notes = $instruction->notes->map(function ($note) {
            return [
                'instruction_note_id' => $note->instruction_note_id,
                'note_title' => $note->note_title,
                'note' => $note->note,
                'note_number' => $note->note_number,
                'icon' => $note->icon,
                'display_order' => $note->display_order,
            ];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'data' => [

                // Basic information
                'admission_instruction_id' =>
                    $instruction->admission_instruction_id,

                'class_id' =>
                    $instruction->class_id,

                'academic_yr' =>
                    $instruction->academic_yr,

                'title' =>
                    $instruction->title,

                'subtitle' =>
                    $instruction->subtitle,

                // 1. Instructions
                'instructions' => $instructions,

                // 2. Important Dates
                'important_dates' => $importantDates,

                // 3. Required Documents
                'required_documents' => $requiredDocuments,

                // 4. Age Criteria
                'age_criteria' => $ageCriteria,

                // 5. Fee Structure
                'fee_structure' => [

                    'total_annual_fee' =>
                        number_format($totalAnnualFee, 2, '.', ''),

                    'installments' =>
                        $installments,

                    'payment_instructions' =>
                        $instruction->installments->first()->payment_instructions
                        ?? null,
                ],

                // 6. Notes
                'notes' => $notes,

                // 7. Final Sections
                'final_sections' => [

                    'contact_heading' =>
                        $instruction->contact_heading,

                    'contact_details' =>
                        $instruction->contact_details,

                    'principal_name' =>
                        $instruction->principal_name,

                    'principal_designation' =>
                        $instruction->principal_designation,

                    'application_button_text' =>
                        $instruction->application_button_text,
                ],
            ]
        ]);
    }
}