<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionDocumentType;
use App\Models\AdmissionUploadDocument;
use App\Models\OnlineAdmissionForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdmissionDocumentController extends Controller
{
    /**
     * Get all active document types.
     *
     * Document types are stored in:
     *
     * admission_document_types
     *
     * Nothing is hardcoded here.
     */
    private function getDocumentTypes()
    {
        return AdmissionDocumentType::where('is_active', 'Y')
            ->orderBy('id')
            ->get();
    }


    /**
     * Find an active document type by code.
     *
     * Example:
     *
     * BC
     * PS
     * FP
     *
     * The code is checked against:
     *
     * admission_document_types
     */
    private function findDocumentType($code)
    {
        return AdmissionDocumentType::where(
            'code',
            strtoupper(trim($code))
        )
            ->where('is_active', 'Y')
            ->first();
    }


    /**
     * Get active document types API.
     *
     * GET:
     *
     * /api/admission/document-types
     *
     * Example response:
     *
     * {
     *     "success": true,
     *     "data": {
     *         "document_types": [
     *             {
     *                 "id": 1,
     *                 "code": "BC",
     *                 "name": "Birth Certificate",
     *                 "is_active": "Y"
     *             }
     *         ]
     *     }
     * }
     */
    public function documentTypes()
    {
        $documentTypes = $this->getDocumentTypes()
            ->map(function ($documentType) {

                return [
                    'id' => $documentType->id,

                    'code' => $documentType->code,

                    'name' => $documentType->name,

                    'is_active' => $documentType->is_active,
                ];
            })
            ->values();


        return response()->json([
            'success' => true,

            'data' => [
                'document_types' => $documentTypes,
            ]
        ]);
    }


    /**
     * Upload admission document.
     *
     * POST:
     *
     * /api/admission/online-form/{formId}/documents
     *
     * Content-Type:
     *
     * multipart/form-data
     *
     * Required:
     *
     * nar_id
     * doc_type
     * document
     */
    public function upload(Request $request, $formId)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate basic request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'nar_id' => [
                'required',
                'integer'
            ],

            /*
             * Do NOT use "in:BC,PS,FP..." here.
             *
             * Document types are dynamic and come from
             * admission_document_types.
             */
            'doc_type' => [
                'required',
                'string',
                'max:20'
            ],

            'document' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,pdf'
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Check admission form
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
        | Check authorization
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
        | Normalize document type
        |--------------------------------------------------------------------------
        */

        $docType = strtoupper(
            trim($validated['doc_type'])
        );


        /*
        |--------------------------------------------------------------------------
        | Find document type from database
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | admission_document_types
        |
        | BC -> Birth Certificate
        | PS -> Student Photo
        |
        */

        $documentType = $this->findDocumentType(
            $docType
        );


        if (!$documentType) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Invalid or inactive document type.'

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Get uploaded file
        |--------------------------------------------------------------------------
        */

        $document = $request->file('document');


        /*
        |--------------------------------------------------------------------------
        | Check duplicate document
        |--------------------------------------------------------------------------
        |
        | Only one document of each type is allowed
        | for one admission form.
        |
        */

        $existingDocument =
            AdmissionUploadDocument::where(
                'form_id',
                $formId
            )
            ->where(
                'doc_type',
                $documentType->code
            )
            ->first();


        if ($existingDocument) {

            return response()->json([

                'success' => false,

                'message' =>
                    $documentType->name .
                    ' has already been uploaded for this admission form.',

                'data' => [

                    'doc_type' =>
                        $documentType->code,

                    'document_type' =>
                        $documentType->name,

                    'image_name' =>
                        $existingDocument->image_name,
                ]

            ], 409);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate file name
        |--------------------------------------------------------------------------
        */

        $originalName = pathinfo(

            $document->getClientOriginalName(),

            PATHINFO_FILENAME
        );


        /*
        |--------------------------------------------------------------------------
        | Clean original file name
        |--------------------------------------------------------------------------
        */

        $originalName = preg_replace(

            '/[^A-Za-z0-9_-]/',

            '_',

            $originalName
        );


        /*
        |--------------------------------------------------------------------------
        | File extension
        |--------------------------------------------------------------------------
        */

        $extension = strtolower(

            $document->getClientOriginalExtension()
        );


        /*
        |--------------------------------------------------------------------------
        | Final file name
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | 2026-27-GRADE-1-0001_bc_birth_certificate.pdf
        |
        */

        $fileName =
            $formId .
            '_' .
            strtolower($documentType->code) .
            '_' .
            $originalName .
            '.' .
            $extension;


        /*
        |--------------------------------------------------------------------------
        | Save physical file
        |--------------------------------------------------------------------------
        */

        $filePath = $document->storeAs(

            'admission_documents',

            $fileName,

            'public'
        );


        /*
        |--------------------------------------------------------------------------
        | Save database record
        |--------------------------------------------------------------------------
        */

        $upload = AdmissionUploadDocument::create([

            'form_id' =>
                $formId,

            'doc_type' =>
                $documentType->code,

            'image_name' =>
                $fileName,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Update admission status
        |--------------------------------------------------------------------------
        */

        $student->admission_form_status =
            'Document Submitted';

        $student->save();


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' =>
                $documentType->name .
                ' uploaded successfully.',

            'data' => [

                'form_id' =>
                    $upload->form_id,

                'doc_type' =>
                    $upload->doc_type,

                'document_type' =>
                    $documentType->name,

                'image_name' =>
                    $upload->image_name,

                'file_path' =>
                    $filePath,

                'document_url' =>
                    asset(
                        'storage/' . $filePath
                    ),

                'admission_form_status' =>
                    $student->admission_form_status,
            ]

        ], 201);
    }


    /**
     * Get all uploaded documents for an admission form.
     *
     * GET:
     *
     * /api/admission/online-form/{formId}/documents
     *
     * Query:
     *
     * ?nar_id=0
     */
    public function index(Request $request, $formId)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'nar_id' => [
                'required',
                'integer'
            ]

        ]);


        /*
        |--------------------------------------------------------------------------
        | Check admission form
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
        | Authorization
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
        | Get uploaded documents
        |--------------------------------------------------------------------------
        */

        $documents =
            AdmissionUploadDocument::where(

                'form_id',

                $formId

            )->get();


        /*
        |--------------------------------------------------------------------------
        | Get document types from database
        |--------------------------------------------------------------------------
        |
        | We load the document types once instead of running
        | a separate query for every uploaded document.
        |
        */

        $documentTypes =
            AdmissionDocumentType::where(

                'is_active',

                'Y'

            )
            ->get()
            ->keyBy('code');


        /*
        |--------------------------------------------------------------------------
        | Add dynamic document information
        |--------------------------------------------------------------------------
        */

        $documents->transform(

            function ($document) use ($documentTypes) {

                $documentType =
                    $documentTypes->get(
                        strtoupper($document->doc_type)
                    );


                /*
                |--------------------------------------------------------------
                | Document name
                |--------------------------------------------------------------
                */

                $document->document_type =
                    $documentType
                        ? $documentType->name
                        : 'Unknown';


                /*
                |--------------------------------------------------------------
                | Document URL
                |--------------------------------------------------------------
                */

                $document->document_url =
                    asset(

                        'storage/admission_documents/' .
                        $document->image_name

                    );


                return $document;
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'data' => [

                'form_id' =>
                    $formId,

                'documents' =>
                    $documents,

                'admission_form_status' =>
                    $student->admission_form_status,
            ]

        ]);
    }


    /**
     * View / download a specific document.
     *
     * GET:
     *
     * /api/admission/online-form/{formId}/documents/{docType}
     *
     * Example:
     *
     * /api/admission/online-form/2026-27-GRADE-1-0001/documents/BC?nar_id=0
     */
    public function view(
        Request $request,
        $formId,
        $docType
    ) {

        /*
        |--------------------------------------------------------------------------
        | Validate request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'nar_id' => [
                'required',
                'integer'
            ]

        ]);


        /*
        |--------------------------------------------------------------------------
        | Normalize document type
        |--------------------------------------------------------------------------
        */

        $docType =
            strtoupper(
                trim($docType)
            );


        /*
        |--------------------------------------------------------------------------
        | Check admission form
        |--------------------------------------------------------------------------
        */

        $student =
            OnlineAdmissionForm::where(

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
        | Authorization
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
        | Find document type dynamically
        |--------------------------------------------------------------------------
        */

        $documentType =
            $this->findDocumentType(
                $docType
            );


        if (!$documentType) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Invalid or inactive document type.'

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Find uploaded document
        |--------------------------------------------------------------------------
        */

        $document =
            AdmissionUploadDocument::where(

                'form_id',

                $formId

            )
            ->where(

                'doc_type',

                $documentType->code

            )
            ->first();


        if (!$document) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Document not found.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Check physical file
        |--------------------------------------------------------------------------
        */

        $filePath =
            'admission_documents/' .
            $document->image_name;


        if (
            !Storage::disk('public')
                ->exists($filePath)
        ) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Document file not found in storage.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'data' => [

                'form_id' =>
                    $document->form_id,

                'doc_type' =>
                    $document->doc_type,

                'document_type' =>
                    $documentType->name,

                'image_name' =>
                    $document->image_name,

                'document_url' =>
                    asset(
                        'storage/' . $filePath
                    ),
            ]

        ]);
    }


    /**
     * Delete a document.
     *
     * DELETE:
     *
     * /api/admission/online-form/{formId}/documents/{docType}
     *
     * Query:
     *
     * ?nar_id=0
     */
    public function destroy(
        Request $request,
        $formId,
        $docType
    ) {

        /*
        |--------------------------------------------------------------------------
        | Validate request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'nar_id' => [
                'required',
                'integer'
            ]

        ]);


        /*
        |--------------------------------------------------------------------------
        | Normalize document type
        |--------------------------------------------------------------------------
        */

        $docType =
            strtoupper(
                trim($docType)
            );


        /*
        |--------------------------------------------------------------------------
        | Check admission form
        |--------------------------------------------------------------------------
        */

        $student =
            OnlineAdmissionForm::where(

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
        | Authorization
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
        | Find document type dynamically
        |--------------------------------------------------------------------------
        */

        $documentType =
            $this->findDocumentType(
                $docType
            );


        if (!$documentType) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Invalid or inactive document type.'

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Find uploaded document
        |--------------------------------------------------------------------------
        */

        $document =
            AdmissionUploadDocument::where(

                'form_id',

                $formId

            )
            ->where(

                'doc_type',

                $documentType->code

            )
            ->first();


        if (!$document) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Document not found.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Delete physical file
        |--------------------------------------------------------------------------
        */

        $filePath =
            'admission_documents/' .
            $document->image_name;


        if (
            Storage::disk('public')
                ->exists($filePath)
        ) {

            Storage::disk('public')
                ->delete($filePath);
        }


        /*
        |--------------------------------------------------------------------------
        | Delete database record
        |--------------------------------------------------------------------------
        */

        $document->delete();


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' =>
                $documentType->name .
                ' deleted successfully.'

        ]);
    }
}