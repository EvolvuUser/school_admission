<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionDocumentType;

class AdmissionDocumentTypeController extends Controller
{
    /**
     * Get active admission document types.
     *
     * GET:
     * /api/admission/document-types
     */
    public function index()
    {
        $documentTypes = AdmissionDocumentType::where('is_active', 'Y')
            ->orderBy('id')
            ->get([
                'code',
                'name',
            ]);

        return response()->json([
            'success' => true,
            'data' => $documentTypes,
        ]);
    }
}