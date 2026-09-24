<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionDocumentType;

class AdmissionDocumentTypeController extends Controller
{
    /**
     * Get all active admission document types.
     *
     * GET:
     *
     * /api/admission/document-types
     *
     * Documents are dynamically divided into:
     *
     * 1. Required documents
     * 2. Optional documents
     *
     * based on admission_document_types.is_required.
     */
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Get all active document types
        |--------------------------------------------------------------------------
        */

        $documentTypes = AdmissionDocumentType::where(
            'is_active',
            'Y'
        )
            ->orderBy('id')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Required Documents
        |--------------------------------------------------------------------------
        */

        $requiredDocuments = $documentTypes
            ->where('is_required', 'Y')
            ->map(function ($documentType) {

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
                ];

            })
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Optional Documents
        |--------------------------------------------------------------------------
        */

        $optionalDocuments = $documentTypes
            ->where('is_required', 'N')
            ->map(function ($documentType) {

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
                ];

            })
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'data' => [

                'required_documents' =>
                    $requiredDocuments,

                'optional_documents' =>
                    $optionalDocuments,

            ]

        ]);
    }
}
