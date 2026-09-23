<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionSignature;
use App\Models\OnlineAdmissionForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdmissionSignatureController extends Controller
{
    /**
     * ============================================================
     * SAVE ADMISSION APPLICATION SIGNATURE
     * ============================================================
     *
     * POST:
     * /api/admission/signature
     *
     * Signature can be:
     *
     * 1. Typed/name signature
     * 2. PDF signature
     *
     * All three declarations are compulsory.
     *
     * Database stores:
     *
     * Y = checked
     *
     */

    public function saveSignature(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | NORMALIZE DECLARATION CHECKBOXES
        |--------------------------------------------------------------------------
        |
        | Frontend/Postman may send:
        |
        | true
        | 1
        | "1"
        | "Y"
        | "yes"
        | "on"
        |
        | We convert all checked values to:
        |
        | Y
        |
        | Unchecked values become:
        |
        | N
        |
        */

        foreach ([
            'declaration_confirmed',
            'terms_accepted',
            'privacy_accepted',
        ] as $field) {

            if ($request->has($field)) {

                $value = $request->input($field);

                $isChecked =
                    $value === true ||
                    $value === 1 ||
                    $value === '1' ||
                    strtoupper((string) $value) === 'Y' ||
                    strtolower((string) $value) === 'yes' ||
                    strtolower((string) $value) === 'true' ||
                    strtolower((string) $value) === 'on';

                $request->merge([
                    $field => $isChecked ? 'Y' : 'N'
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDATE REQUEST
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Application
            |--------------------------------------------------------------------------
            */

            'form_id' =>
                'required|string|max:50',


            /*
            |--------------------------------------------------------------------------
            | Signature Type
            |--------------------------------------------------------------------------
            */

            'signature_type' =>
                'required|in:typed,pdf',


            /*
            |--------------------------------------------------------------------------
            | Typed Signature
            |--------------------------------------------------------------------------
            */

            'signature_name' =>
                'nullable|string|max:255',


            /*
            |--------------------------------------------------------------------------
            | PDF Signature
            |--------------------------------------------------------------------------
            */

            'signature_file' =>
                'nullable|file|mimes:pdf|max:5120',


            /*
            |--------------------------------------------------------------------------
            | Mandatory Declarations
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | All three must be Y.
            |
            */

            'declaration_confirmed' =>
                'required|in:Y',

            'terms_accepted' =>
                'required|in:Y',

            'privacy_accepted' =>
                'required|in:Y',

        ]);


        /*
        |--------------------------------------------------------------------------
        | FIND APPLICATION
        |--------------------------------------------------------------------------
        */

        $application =
            OnlineAdmissionForm::where(
                'form_id',
                $validated['form_id']
            )->first();


        if (!$application) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Admission application not found.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDATE SIGNATURE TYPE
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | TYPED / NAME SIGNATURE
        |--------------------------------------------------------------------------
        */

        if (
            $validated['signature_type'] === 'typed'
        ) {

            if (
                empty(
                    trim(
                        $validated['signature_name'] ?? ''
                    )
                )
            ) {

                return response()->json([

                    'success' => false,

                    'message' =>
                        'Signature name is required.'

                ], 422);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PDF SIGNATURE
        |--------------------------------------------------------------------------
        */

        if (
            $validated['signature_type'] === 'pdf'
        ) {

            if (!$request->hasFile('signature_file')) {

                return response()->json([

                    'success' => false,

                    'message' =>
                        'Signature PDF is required.'

                ], 422);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | EXISTING SIGNATURE
        |--------------------------------------------------------------------------
        */

        $existingSignature =
            AdmissionSignature::where(
                'form_id',
                $validated['form_id']
            )->first();


        /*
        |--------------------------------------------------------------------------
        | DELETE OLD PDF
        |--------------------------------------------------------------------------
        |
        | If the user already has a signature and uploads/replaces
        | another PDF, delete the previous PDF.
        |
        */

        if (
            $existingSignature &&
            $existingSignature->signature_file_path
        ) {

            /*
             * Only delete the old file when a new signature is
             * actually being saved.
             */

            if (
                $validated['signature_type'] === 'pdf' ||
                $validated['signature_type'] === 'typed'
            ) {

                Storage::disk('public')->delete(
                    $existingSignature->signature_file_path
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PREPARE SIGNATURE DATA
        |--------------------------------------------------------------------------
        */

        $signatureData = [

            /*
            |--------------------------------------------------------------------------
            | Form
            |--------------------------------------------------------------------------
            */

            'form_id' =>
                $validated['form_id'],


            /*
            |--------------------------------------------------------------------------
            | Signature Type
            |--------------------------------------------------------------------------
            */

            'signature_type' =>
                $validated['signature_type'],


            /*
            |--------------------------------------------------------------------------
            | Default Signature Values
            |--------------------------------------------------------------------------
            */

            'signature_name' =>
                null,

            'signature_file_path' =>
                null,


            /*
            |--------------------------------------------------------------------------
            | DECLARATIONS
            |--------------------------------------------------------------------------
            |
            | Since validation requires all three to be Y,
            | we explicitly save Y.
            |
            */

            'declaration_confirmed' =>
                'Y',

            'terms_accepted' =>
                'Y',

            'privacy_accepted' =>
                'Y',

        ];


        /*
        |--------------------------------------------------------------------------
        | SAVE TYPED / NAME SIGNATURE
        |--------------------------------------------------------------------------
        */

        if (
            $validated['signature_type'] === 'typed'
        ) {

            $signatureData['signature_name'] =
                strtoupper(
                    trim(
                        $validated['signature_name']
                    )
                );

            /*
             * No PDF for typed signature.
             */

            $signatureData['signature_file_path'] =
                null;
        }


        /*
        |--------------------------------------------------------------------------
        | SAVE PDF SIGNATURE
        |--------------------------------------------------------------------------
        */

        if (
            $validated['signature_type'] === 'pdf'
        ) {

            $file =
                $request->file('signature_file');


            /*
            |--------------------------------------------------------------------------
            | Store PDF
            |--------------------------------------------------------------------------
            */

            $filePath =
                $file->store(
                    'admission-signatures',
                    'public'
                );


            $signatureData['signature_file_path'] =
                $filePath;


            /*
             * No typed name for PDF signature.
             */

            $signatureData['signature_name'] =
                null;
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE / UPDATE SIGNATURE
        |--------------------------------------------------------------------------
        */

        $signature =
            AdmissionSignature::updateOrCreate(

                [
                    'form_id' =>
                        $validated['form_id'],
                ],

                $signatureData

            );


        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                true,

            'message' =>
                'Signature and declarations saved successfully.',

            'data' => [

                'signature_id' =>
                    $signature->signature_id,

                'form_id' =>
                    $signature->form_id,

                'signature_type' =>
                    $signature->signature_type,

                'signature_name' =>
                    $signature->signature_name,

                'signature_file_path' =>
                    $signature->signature_file_path,

                /*
                |--------------------------------------------------------------------------
                | Declarations
                |--------------------------------------------------------------------------
                */

                'declaration_confirmed' =>
                    $signature->declaration_confirmed,

                'terms_accepted' =>
                    $signature->terms_accepted,

                'privacy_accepted' =>
                    $signature->privacy_accepted,

            ]

        ], 201);
    }


    /**
     * ============================================================
     * GET SIGNATURE
     * ============================================================
     *
     * GET:
     * /api/admission/signature/{formId}
     */

    public function getSignature($formId)
    {
        /*
        |--------------------------------------------------------------------------
        | Find Signature
        |--------------------------------------------------------------------------
        */

        $signature =
            AdmissionSignature::where(
                'form_id',
                $formId
            )->first();


        if (!$signature) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Signature not found.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Return Signature
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'data' => [

                'signature_id' =>
                    $signature->signature_id,

                'form_id' =>
                    $signature->form_id,

                'signature_type' =>
                    $signature->signature_type,

                'signature_name' =>
                    $signature->signature_name,

                'signature_file_path' =>
                    $signature->signature_file_path,

                'signature_file_url' =>
                    $signature->signature_file_path
                        ? Storage::disk('public')->url(
                            $signature->signature_file_path
                        )
                        : null,


                /*
                |--------------------------------------------------------------------------
                | Declarations
                |--------------------------------------------------------------------------
                |
                | Database values will be:
                |
                | Y
                |
                */

                'declaration_confirmed' =>
                    $signature->declaration_confirmed,

                'terms_accepted' =>
                    $signature->terms_accepted,

                'privacy_accepted' =>
                    $signature->privacy_accepted,

            ]

        ]);
    }
}