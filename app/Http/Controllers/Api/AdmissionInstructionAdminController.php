<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdmissionInstructionAdminController extends Controller
{
    /**
     * Store complete admission instruction page
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            /*
            |--------------------------------------------------------------------------
            | Main Instruction
            |--------------------------------------------------------------------------
            */

            'class_id' => 'required|integer',
            'academic_yr' => 'required|string|max:20',

            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',

            'instructions_heading' => 'nullable|string|max:255',
            'dates_heading' => 'nullable|string|max:255',
            'documents_heading' => 'nullable|string|max:255',
            'age_criteria_heading' => 'nullable|string|max:255',
            'notes_heading' => 'nullable|string|max:255',
            'contact_heading' => 'nullable|string|max:255',

            'contact_details' => 'nullable|string',
            'principal_name' => 'nullable|string|max:255',
            'principal_designation' => 'nullable|string|max:255',

            'application_button_text' => 'nullable|string|max:255',
            'is_active' => 'nullable|in:Y,N',


            /*
            |--------------------------------------------------------------------------
            | Instruction Items
            |--------------------------------------------------------------------------
            */

            'items' => 'nullable|array',

            'items.*.instruction' => 'required|string',
            'items.*.display_order' => 'nullable|integer',
            'items.*.is_active' => 'nullable|in:Y,N',


            /*
            |--------------------------------------------------------------------------
            | Important Dates
            |--------------------------------------------------------------------------
            */

            'dates' => 'nullable|array',

            'dates.*.event_date' => 'nullable|date',
            'dates.*.event_time' => 'nullable|string|max:50',
            'dates.*.description' => 'required|string',
            'dates.*.display_order' => 'nullable|integer',
            'dates.*.is_active' => 'nullable|in:Y,N',


            /*
            |--------------------------------------------------------------------------
            | Required Documents
            |--------------------------------------------------------------------------
            */

            'documents' => 'nullable|array',

            'documents.*.document_title' => 'required|string|max:255',
            'documents.*.description' => 'nullable|string',
            'documents.*.is_mandatory' => 'nullable|in:Y,N',
            'documents.*.display_order' => 'nullable|integer',
            'documents.*.is_active' => 'nullable|in:Y,N',


            /*
            |--------------------------------------------------------------------------
            | Fee Structure / Installments
            |--------------------------------------------------------------------------
            |
            | One installment record contains:
            | first_installment
            | second_installment
            | third_installment
            | payment_instructions
            |
            */

            'installments' => 'nullable|array',

            'installments.*.first_installment' => 'nullable|numeric|min:0',
            'installments.*.second_installment' => 'nullable|numeric|min:0',
            'installments.*.third_installment' => 'nullable|numeric|min:0',

            'installments.*.payment_instructions' => 'nullable|string',

            'installments.*.display_order' => 'nullable|integer',
            'installments.*.is_active' => 'nullable|in:Y,N',


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' => 'nullable|array',

            'notes.*.note_title' => 'nullable|string|max:255',
            'notes.*.note' => 'required|string',
            'notes.*.note_number' => 'nullable|integer',
            'notes.*.icon' => 'nullable|string|max:100',
            'notes.*.display_order' => 'nullable|integer',
            'notes.*.is_active' => 'nullable|in:Y,N',


            /*
            |--------------------------------------------------------------------------
            | Age Criteria
            |--------------------------------------------------------------------------
            */

            'age_criteria' => 'nullable|array',

            'age_criteria.*.class_id' =>
                'required|integer|exists:class,class_id',

            'age_criteria.*.heading' =>
                'nullable|string|max:255',

            'age_criteria.*.description' =>
                'nullable|string',

            'age_criteria.*.display_order' =>
                'nullable|integer',

            'age_criteria.*.is_active' =>
                'nullable|in:Y,N',

        ]);


        /*
        |--------------------------------------------------------------------------
        | Validation Error
        |--------------------------------------------------------------------------
        */

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Save Everything in One Transaction
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Create Main Instruction
            |--------------------------------------------------------------------------
            */

            $instructionId = DB::table('admission_instructions')
                ->insertGetId([

                    'class_id' => $request->class_id,

                    'academic_yr' => $request->academic_yr,

                    'title' => $request->title,

                    'subtitle' => $request->subtitle,

                    'instructions_heading' =>
                        $request->instructions_heading,

                    'dates_heading' =>
                        $request->dates_heading,

                    'documents_heading' =>
                        $request->documents_heading,

                    'age_criteria_heading' =>
                        $request->age_criteria_heading,

                    'notes_heading' =>
                        $request->notes_heading,

                    'contact_heading' =>
                        $request->contact_heading,

                    'contact_details' =>
                        $request->contact_details,

                    'principal_name' =>
                        $request->principal_name,

                    'principal_designation' =>
                        $request->principal_designation,

                    'application_button_text' =>
                        $request->application_button_text
                        ?? 'Proceed to Application Form',

                    'is_active' =>
                        $request->is_active ?? 'Y',

                    'created_at' => now(),

                    'updated_at' => now(),
                ]);


            /*
            |--------------------------------------------------------------------------
            | 2. Save Main Instructions
            |--------------------------------------------------------------------------
            */

            if ($request->has('items')) {

                foreach ($request->items as $index => $item) {

                    DB::table('admission_instruction_items')
                        ->insert([

                            'admission_instruction_id' =>
                                $instructionId,

                            'instruction' =>
                                $item['instruction'],

                            'display_order' =>
                                $item['display_order']
                                ?? ($index + 1),

                            'is_active' =>
                                $item['is_active'] ?? 'Y',

                            'created_at' => now(),

                            'updated_at' => now(),
                        ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 3. Save Important Dates
            |--------------------------------------------------------------------------
            */

            if ($request->has('dates')) {

                foreach ($request->dates as $index => $date) {

                    DB::table('admission_instruction_dates')
                        ->insert([

                            'admission_instruction_id' =>
                                $instructionId,

                            'event_date' =>
                                $date['event_date'] ?? null,

                            'event_time' =>
                                $date['event_time'] ?? null,

                            'description' =>
                                $date['description'],

                            'display_order' =>
                                $date['display_order']
                                ?? ($index + 1),

                            'is_active' =>
                                $date['is_active'] ?? 'Y',

                            'created_at' => now(),

                            'updated_at' => now(),
                        ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 4. Save Required Documents
            |--------------------------------------------------------------------------
            */

            if ($request->has('documents')) {

                foreach ($request->documents as $index => $document) {

                    DB::table('admission_instruction_documents')
                        ->insert([

                            'admission_instruction_id' =>
                                $instructionId,

                            'document_title' =>
                                $document['document_title'],

                            'description' =>
                                $document['description'] ?? null,

                            'is_mandatory' =>
                                $document['is_mandatory'] ?? 'Y',

                            'display_order' =>
                                $document['display_order']
                                ?? ($index + 1),

                            'is_active' =>
                                $document['is_active'] ?? 'Y',

                            'created_at' => now(),

                            'updated_at' => now(),
                        ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 5. Save Fee Structure / Installments
            |--------------------------------------------------------------------------
            */

            if ($request->has('installments')) {

                foreach ($request->installments as $index => $installment) {

                    DB::table('admission_instruction_installments')
                        ->insert([

                            'admission_instruction_id' =>
                                $instructionId,

                            'first_installment' =>
                                $installment['first_installment']
                                ?? 0,

                            'second_installment' =>
                                $installment['second_installment']
                                ?? 0,

                            'third_installment' =>
                                $installment['third_installment']
                                ?? 0,

                            'payment_instructions' =>
                                $installment['payment_instructions']
                                ?? null,

                            'display_order' =>
                                $installment['display_order']
                                ?? ($index + 1),

                            'is_active' =>
                                $installment['is_active'] ?? 'Y',

                            'created_at' => now(),

                            'updated_at' => now(),
                        ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 6. Save Notes
            |--------------------------------------------------------------------------
            */

            if ($request->has('notes')) {

                foreach ($request->notes as $index => $note) {

                    DB::table('admission_instruction_notes')
                        ->insert([

                            'admission_instruction_id' =>
                                $instructionId,

                            'note_title' =>
                                $note['note_title'] ?? null,

                            'note' =>
                                $note['note'],

                            'note_number' =>
                                $note['note_number']
                                ?? ($index + 1),

                            'icon' =>
                                $note['icon'] ?? null,

                            'display_order' =>
                                $note['display_order']
                                ?? ($index + 1),

                            'is_active' =>
                                $note['is_active'] ?? 'Y',

                            'created_at' => now(),

                            'updated_at' => now(),
                        ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 7. Save Age Criteria
            |--------------------------------------------------------------------------
            */

            if ($request->has('age_criteria')) {

                foreach ($request->age_criteria as $index => $ageCriteria) {

                    DB::table('admission_instruction_age_criteria')
                        ->insert([

                            'admission_instruction_id' =>
                                $instructionId,

                            'class_id' =>
                                $ageCriteria['class_id'],

                            'heading' =>
                                $ageCriteria['heading'] ?? null,

                            'description' =>
                                $ageCriteria['description'] ?? null,

                            'display_order' =>
                                $ageCriteria['display_order']
                                ?? ($index + 1),

                            'is_active' =>
                                $ageCriteria['is_active'] ?? 'Y',

                            'created_at' => now(),

                            'updated_at' => now(),
                        ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Commit Transaction
            |--------------------------------------------------------------------------
            */

            DB::commit();


            return response()->json([

                'success' => true,

                'message' =>
                    'Admission instruction page created successfully.',

                'admission_instruction_id' =>
                    $instructionId,

            ], 201);


        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>
                    'Failed to create admission instruction page.',

                'error' =>
                    $e->getMessage(),

            ], 500);
        }
    }
}