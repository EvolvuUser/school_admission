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
     * Document types are dynamically stored in:
     *
     * admission_document_types
     */
    private function getDocumentTypes()
    {
        return AdmissionDocumentType::where('is_active', 'Y')
            ->orderBy('id')
            ->get();
    }

    /**
     * Find an active document type by code.
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
     * Get active document types.
     *
     * GET:
     *
     * /api/admission/document-types
     */
    public function documentTypes()
    {
        $documentTypes = $this->getDocumentTypes()
            ->map(function ($documentType) {

                return [
                    'id' => $documentType->id,
                    'code' => $documentType->code,
                    'name' => $documentType->name,
                    'is_required' => $documentType->is_required,
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
     *
     * Document types are dynamic and are taken from:
     *
     * admission_document_types
     */
    public function upload(Request $request, $formId)
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
            ],

            /*
             * Do not hardcode document codes here.
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
                'message' => 'Online admission form not found.'
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
        | Find document type dynamically
        |--------------------------------------------------------------------------
        */

        $documentType = $this->findDocumentType($docType);

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
        | Only one document of each type is allowed for
        | one admission form.
        |
        */

        $existingDocument = AdmissionUploadDocument::where(
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
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | No admission_form_status update is performed here.
        |
        | The upload API only uploads the document.
        |
        | Required-document completion is checked dynamically
        | through the index() API using:
        |
        | admission_document_types.is_required
        |
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

                'is_required' =>
                    $documentType->is_required,

                'image_name' =>
                    $upload->image_name,

                'file_path' =>
                    $filePath,

                'document_url' =>
                    asset(
                        'storage/' . $filePath
                    ),
            ]

        ], 201);
    }

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
    | Get Uploaded Documents
    |--------------------------------------------------------------------------
    */

    $documents = AdmissionUploadDocument::where(
        'form_id',
        $formId
    )->get();


    /*
    |--------------------------------------------------------------------------
    | Get ALL Active Document Types
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | We get all active documents here.
    |
    | We DO NOT filter by is_required = Y.
    |
    | Therefore both required and optional documents
    | will be returned.
    |
    */

    $documentTypes = AdmissionDocumentType::where(
        'is_active',
        'Y'
    )
        ->orderBy('id')
        ->get();


    /*
    |--------------------------------------------------------------------------
    | Create Uploaded Document Code List
    |--------------------------------------------------------------------------
    */

    $uploadedDocumentCodes = $documents
        ->pluck('doc_type')
        ->map(function ($code) {

            return strtoupper(
                trim($code)
            );

        })
        ->unique()
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Add Information To Uploaded Documents
    |--------------------------------------------------------------------------
    |
    | This keeps the uploaded documents section useful.
    |
    | Every uploaded document gets:
    |
    | - document_type
    | - is_required
    | - document_url
    |
    */

    $documents->transform(
        function ($document) use ($documentTypes) {

            $documentType = $documentTypes->first(
                function ($type) use ($document) {

                    return strtoupper(
                        trim($type->code)
                    ) === strtoupper(
                        trim($document->doc_type)
                    );
                }
            );


            /*
            |------------------------------------------------------------------
            | Document Name
            |------------------------------------------------------------------
            */

            $document->document_type =
                $documentType
                    ? $documentType->name
                    : 'Unknown';


            /*
            |------------------------------------------------------------------
            | Required / Optional
            |------------------------------------------------------------------
            */

            $document->is_required =
                $documentType
                    ? $documentType->is_required
                    : 'N';


            /*
            |------------------------------------------------------------------
            | Uploaded
            |------------------------------------------------------------------
            */

            $document->uploaded = true;


            /*
            |------------------------------------------------------------------
            | Document URL
            |------------------------------------------------------------------
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
    | Build COMPLETE Document Status List
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | This contains ALL active documents.
    |
    | Example:
    |
    | BC   -> required -> uploaded
    | PS   -> required -> not uploaded
    | FP   -> optional -> not uploaded
    | SC   -> optional -> not uploaded
    | BPSC -> optional -> not uploaded
    | AC   -> optional -> not uploaded
    | CC   -> optional -> not uploaded
    | PC   -> optional -> not uploaded
    |
    */

    $allDocuments = $documentTypes
        ->map(function ($documentType) use (
            $uploadedDocumentCodes
        ) {

            $isUploaded =
                $uploadedDocumentCodes->contains(
                    strtoupper(
                        trim($documentType->code)
                    )
                );


            return [

                'id' =>
                    $documentType->id,

                'code' =>
                    $documentType->code,

                'name' =>
                    $documentType->name,

                'is_required' =>
                    $documentType->is_required,

                'is_active' =>
                    $documentType->is_active,

                'uploaded' =>
                    $isUploaded,

            ];

        })
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Required Documents
    |--------------------------------------------------------------------------
    |
    | This is only the required subset of allDocuments.
    |
    */

    $requiredDocuments = $allDocuments
        ->where('is_required', 'Y')
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Optional Documents
    |--------------------------------------------------------------------------
    |
    | This is only the optional subset of allDocuments.
    |
    */

    $optionalDocuments = $allDocuments
        ->where('is_required', 'N')
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Missing Required Documents
    |--------------------------------------------------------------------------
    |
    | Only required documents which have NOT been uploaded.
    |
    */

    $missingRequiredDocuments = $requiredDocuments
        ->filter(function ($document) {

            return $document['uploaded'] === false;

        })
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Check Required Document Completion
    |--------------------------------------------------------------------------
    */

    $allRequiredDocumentsUploaded =
        $missingRequiredDocuments->isEmpty();


    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    return response()->json([

        'success' => true,

        'data' => [

            /*
            |------------------------------------------------------------------
            | Form
            |------------------------------------------------------------------
            */

            'form_id' =>
                $formId,


            /*
            |------------------------------------------------------------------
            | Uploaded Documents
            |------------------------------------------------------------------
            */

            'documents' =>
                $documents,


            /*
            |------------------------------------------------------------------
            | ALL Documents
            |------------------------------------------------------------------
            |
            | This is the important new section.
            |
            | It contains all 8 documents with:
            |
            | is_required
            | uploaded
            |
            */

            'all_documents' =>
                $allDocuments,


            /*
            |------------------------------------------------------------------
            | Required Documents
            |------------------------------------------------------------------
            */

            'required_documents' =>
                $requiredDocuments,


            /*
            |------------------------------------------------------------------
            | Optional Documents
            |------------------------------------------------------------------
            */

            'optional_documents' =>
                $optionalDocuments,


            /*
            |------------------------------------------------------------------
            | Missing Required Documents
            |------------------------------------------------------------------
            */

            'missing_required_documents' =>
                $missingRequiredDocuments,


            /*
            |------------------------------------------------------------------
            | Required Document Completion
            |------------------------------------------------------------------
            */

            'all_required_documents_uploaded' =>
                $allRequiredDocumentsUploaded,


            /*
            |------------------------------------------------------------------
            | Existing Admission Status
            |------------------------------------------------------------------
            |
            | This controller does NOT change the status.
            |
            */

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

        $docType = strtoupper(
            trim($docType)
        );

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
        | Find document type dynamically
        |--------------------------------------------------------------------------
        */

        $documentType =
            $this->findDocumentType($docType);

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

        $document = AdmissionUploadDocument::where(
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

                'is_required' =>
                    $documentType->is_required,

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

        $docType = strtoupper(
            trim($docType)
        );

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
        | Find document type dynamically
        |--------------------------------------------------------------------------
        */

        $documentType =
            $this->findDocumentType($docType);

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

        $document = AdmissionUploadDocument::where(
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