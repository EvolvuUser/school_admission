<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdmissionController;

Route::get('/', function () {
    return view('welcome');
});

Route::post(
    '/api/admission/mobile/send-otp',
    [AdmissionController::class, 'sendOtp']
);