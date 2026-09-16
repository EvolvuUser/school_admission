<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdmissionUploadDocument;
use App\Models\OnlineAdmissionForm;
use Illuminate\Support\Facades\Storage;

class AdmissionDocumentController extends Controller
{
    /**
     * Supported document types.
     */
    private const DOCUMENT_TYPES = [
        'BC'   => 'Birth Certificate',
        'PS'   => 'Student Photo',
        'FP'   => 'Family Photo',
        'SC'   => 'Student Certificate / Bonafide Certificate',
        'BPSC' => 'Baptism Certificate',
        'AC'   => 'Aadhar Card',
        'CC'   => 'Caste Certificate',
        'PC'   => 'Parent Aadhar Card',
    ];


    /**
     * Upload admission document.
     *
     * POST:
     * /api/admission/online-form/{formId}/documents
     *
     * Content-Type:
     * multipart/form-data
     */
    public function upload(Request $request, $formId)
    {
        $validated = $request->validate([
            'nar_id' => 'required|integer',
            'doc_type' => 'required|string|in:BC,PS,FP,SC,BPSC,AC,CC,PC',

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

        $student = OnlineAdmissionForm::where('form_id', $formId)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Online admission form not found.'
            ], 404);
        }

        if ((int) $student->nar_id !== (int) $validated['nar_id']) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this admission form.'
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate request
        |--------------------------------------------------------------------------
        */

        $docType = strtoupper($validated['doc_type']);

        $document = $request->file('document');


        /*
        |--------------------------------------------------------------------------
        | Check duplicate document
        |--------------------------------------------------------------------------
        |
        | Only one document of each type is allowed for one admission form.
        |
        */

        $existingDocument = AdmissionUploadDocument::where('form_id', $formId)
            ->where('doc_type', $docType)
            ->first();

        if ($existingDocument) {
            return response()->json([
                'success' => false,
                'message' => 'A ' . self::DOCUMENT_TYPES[$docType] .
                    ' has already been uploaded for this admission form.',
                'data' => [
                    'doc_type' => $docType,
                    'image_name' => $existingDocument->image_name,
                ]
            ], 409);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate file name
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | ABC-2026-X-1234_bc_birth_certificate.pdf
        |
        */

        $originalName = pathinfo(
            $document->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        /*
        | Remove unwanted characters from original filename.
        */
        $originalName = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '_',
            $originalName
        );

        $extension = strtolower(
            $document->getClientOriginalExtension()
        );


        $fileName = $formId
            . '_'
            . strtolower($docType)
            . '_'
            . $originalName
            . '.'
            . $extension;


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
            'form_id' => $formId,
            'doc_type' => $docType,
            'image_name' => $fileName,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Update admission status
        |--------------------------------------------------------------------------
        */

        $student->admission_form_status = 'Document Submitted';
        $student->save();


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' => self::DOCUMENT_TYPES[$docType]
                . ' uploaded successfully.',

            'data' => [
                'form_id' => $upload->form_id,

                'doc_type' => $upload->doc_type,

                'document_type' => self::DOCUMENT_TYPES[$docType],

                'image_name' => $upload->image_name,

                'file_path' => $filePath,

                'document_url' => asset(
                    'storage/' . $filePath
                ),

                'admission_form_status' =>
                    $student->admission_form_status,
            ]
        ], 201);
    }


    /**
     * Get all documents for an admission form.
     *
     * GET:
     * /api/admission/online-form/{formId}/documents
     */
    public function index(Request $request, $formId)
    {
        $validated = $request->validate(['nar_id' => 'required|integer']);

        /*
        |--------------------------------------------------------------------------
        | Check admission form
        |--------------------------------------------------------------------------
        */

        $student = OnlineAdmissionForm::where('form_id', $formId)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Online admission form not found.'
            ], 404);
        }

        if ((int) $student->nar_id !== (int) $validated['nar_id']) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this admission form.'
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Get documents
        |--------------------------------------------------------------------------
        */

        $documents = AdmissionUploadDocument::where(
            'form_id',
            $formId
        )->get();


        /*
        |--------------------------------------------------------------------------
        | Add document URL
        |--------------------------------------------------------------------------
        */

        $documents->transform(function ($document) {

            $document->document_type =
                self::DOCUMENT_TYPES[$document->doc_type]
                ?? 'Unknown';

            $document->document_url = asset(
                'storage/admission_documents/'
                . $document->image_name
            );

            return $document;
        });


        return response()->json([
            'success' => true,

            'data' => [
                'form_id' => $formId,

                'documents' => $documents,

                'admission_form_status' =>
                    $student->admission_form_status,
            ]
        ]);
    }


    /**
     * View / download a specific document.
     *
     * GET:
     * /api/admission/online-form/{formId}/documents/{docType}
     */
    public function view(Request $request, $formId, $docType)
    {
        $validated = $request->validate(['nar_id' => 'required|integer']);
        $docType = strtoupper($docType);

        $student = OnlineAdmissionForm::where('form_id', $formId)->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Online admission form not found.'], 404);
        }

        if ((int) $student->nar_id !== (int) $validated['nar_id']) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to access this admission form.'], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate document type
        |--------------------------------------------------------------------------
        */

        if (!array_key_exists($docType, self::DOCUMENT_TYPES)) {

            return response()->json([
                'success' => false,
                'message' => 'Invalid document type.'
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Find document
        |--------------------------------------------------------------------------
        */

        $document = AdmissionUploadDocument::where(
            'form_id',
            $formId
        )
            ->where('doc_type', $docType)
            ->first();


        if (!$document) {

            return response()->json([
                'success' => false,
                'message' => 'Document not found.'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Check physical file
        |--------------------------------------------------------------------------
        */

        $filePath = 'admission_documents/'
            . $document->image_name;


        if (!Storage::disk('public')->exists($filePath)) {

            return response()->json([
                'success' => false,
                'message' => 'Document file not found in storage.'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Return document information
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'data' => [
                'form_id' => $document->form_id,

                'doc_type' => $document->doc_type,

                'document_type' =>
                    self::DOCUMENT_TYPES[$docType],

                'image_name' =>
                    $document->image_name,

                'document_url' =>
                    asset('storage/' . $filePath),
            ]
        ]);
    }


    /**
     * Delete a document.
     *
     * DELETE:
     * /api/admission/online-form/{formId}/documents/{docType}
     */
    public function destroy(Request $request, $formId, $docType)
    {
        $validated = $request->validate(['nar_id' => 'required|integer']);
        $docType = strtoupper($docType);

        $student = OnlineAdmissionForm::where('form_id', $formId)->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Online admission form not found.'], 404);
        }

        if ((int) $student->nar_id !== (int) $validated['nar_id']) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to access this admission form.'], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Find document
        |--------------------------------------------------------------------------
        */

        $document = AdmissionUploadDocument::where(
            'form_id',
            $formId
        )
            ->where('doc_type', $docType)
            ->first();


        if (!$document) {

            return response()->json([
                'success' => false,
                'message' => 'Document not found.'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Delete physical file
        |--------------------------------------------------------------------------
        */

        $filePath = 'admission_documents/'
            . $document->image_name;


        if (Storage::disk('public')->exists($filePath)) {

            Storage::disk('public')->delete($filePath);
        }


        /*
        |--------------------------------------------------------------------------
        | Delete database record
        |--------------------------------------------------------------------------
        */

        $document->delete();


        return response()->json([
            'success' => true,

            'message' => 'Document deleted successfully.'
        ]);
    }
}