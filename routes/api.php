<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdmissionController;
use App\Http\Controllers\Api\AdmissionInstructionAdminController;
use App\Http\Controllers\Api\AdmissionInstructionController;
use App\Http\Controllers\Api\AdmissionFormController;
use App\Http\Controllers\Api\AdmissionFormFieldOptionController;
use App\Http\Controllers\Api\OnlineAdmissionFormController;
use App\Http\Controllers\Api\AdmissionDocumentController;
use App\Http\Controllers\Api\AdmissionPaymentController;



Route::get('/admission/registration/{narId}', [AdmissionController::class, 'getRegistrationDetails']);
Route::post('/admission/registration', [AdmissionController::class, 'createRegistration']);
Route::post('/admission/send-otp', [AdmissionController::class, 'sendOtp']);
Route::post('/admission/verify-otp', [AdmissionController::class, 'verifyOtp']);
Route::post('/admission/resend-otp', [AdmissionController::class, 'resendOtp']);
Route::get('/admission/classes', [AdmissionController::class, 'getClasses']);
Route::get('/admission/form-fee', [AdmissionController::class, 'getFormFee']);
Route::get('/admission/dashboard', [AdmissionController::class, 'getDashboard']);
Route::post(
    '/admission/admin/instructions',
    [AdmissionInstructionAdminController::class, 'store']
);
Route::get(
    '/admission/instructions/{classId}',
    [AdmissionInstructionController::class, 'getInstructions']
);
Route::get(
    '/admission/form/{formId}',
    [AdmissionFormController::class, 'getFormDetails']
);
Route::get(
    '/admission/form-field-options',
    [AdmissionFormFieldOptionController::class, 'getOptions']
);
Route::post(
    '/admission/student-details',
    [AdmissionFormController::class, 'saveStudentDetails']
);

Route::get(
    '/admission/online-forms',
    [OnlineAdmissionFormController::class, 'index']
);

Route::get(
    '/admission/online-form/{id}',
    [OnlineAdmissionFormController::class, 'show']
);

Route::put(
    '/admission/online-form/{id}',
    [OnlineAdmissionFormController::class, 'update']
);

Route::delete(
    '/admission/online-form/{id}',
    [OnlineAdmissionFormController::class, 'destroy']
);
//Admission Document APIs

// Upload document
Route::post(
    '/admission/online-form/{formId}/documents',
    [AdmissionDocumentController::class, 'upload']
);

// Get all documents
Route::get(
    '/admission/online-form/{formId}/documents',
    [AdmissionDocumentController::class, 'index']
);

// View document information / URL
Route::get(
    '/admission/online-form/{formId}/documents/{docType}',
    [AdmissionDocumentController::class, 'view']
);

// Delete document
Route::delete(
    '/admission/online-form/{formId}/documents/{docType}',
    [AdmissionDocumentController::class, 'destroy']
);
Route::post(
    '/admission/payment/create',
    [AdmissionPaymentController::class, 'createPayment']
);
