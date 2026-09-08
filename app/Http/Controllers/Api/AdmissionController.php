<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\AdmissionUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Services\SmsService;
use App\Http\Services\WhatsAppService;
use App\Http\Services\SmartMailer;

class AdmissionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Get Registration Details
    |--------------------------------------------------------------------------
    */

    public function getRegistrationDetails($narId)
    {
        $registration = Admission::where('nar_id', $narId)->get();

        return response()->json([
            'success' => true,
            'data' => $registration
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Registration
    |--------------------------------------------------------------------------
    */

    public function createRegistration(Request $request)
    {
        $validated = $request->validate([
            'parent_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_no' => 'required|string|max:20',
        ]);

        $registration = Admission::create([
            'parent_name' => $validated['parent_name'],
            'email' => $validated['email'],
            'phone_no' => $validated['phone_no'],
            'date' => now()->toDateString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration created successfully',
            'data' => $registration
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Send OTP
    |--------------------------------------------------------------------------
    */

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:mobile,email',
            'value' => 'required|string',
            'parent_name' => 'nullable|string|max:255',
            'school_id' => 'required|integer',
        ]);

        $type = $validated['type'];
        $value = $validated['value'];
        $schoolId = $validated['school_id'];

$school = DB::table('school_settings')
    ->where('school_id', $schoolId)
    ->where('is_active', 'Y')
    ->first();

if (!$school) {
    return response()->json([
        'success' => false,
        'message' => 'School not found.'
    ], 404);
}

        /*
        |--------------------------------------------------------------------------
        | Find Registration
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {
            $registration = Admission::where('phone_no', $value)->first();
        } else {
            $registration = Admission::where('email', $value)->first();
        }


        /*
        |--------------------------------------------------------------------------
        | Create New Registration
        |--------------------------------------------------------------------------
        */

        if (!$registration) {

            if (empty($validated['parent_name'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent name is required for new registration.'
                ], 422);
            }

            $registration = Admission::create([
                'parent_name' => $validated['parent_name'],
                'email' => $type === 'email' ? $value : ' ',
                'phone_no' => $type === 'mobile' ? $value : ' ',
                'date' => now()->toDateString(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Find User Master
        |--------------------------------------------------------------------------
        */

        $user = AdmissionUser::where('nar_id', $registration->nar_id)
            ->where('user_id', $value)
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Existing OTP
        |--------------------------------------------------------------------------
        */

        if ($user && preg_match('/^[0-9]{5}$/', $user->password)) {

            return response()->json([
                'success' => true,
                'user_type' => 'existing',
                'message' => 'OTP already exists. Please use your existing OTP.',
                'nar_id' => $registration->nar_id
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate OTP
        |--------------------------------------------------------------------------
        */

        $otp = rand(10000, 99999);


        /*
        |--------------------------------------------------------------------------
        | Create / Update User Master
        |--------------------------------------------------------------------------
        */

        if ($user) {

            $user->update([
                'password' => $otp,
                'otp_generated_at' => now(),
                'IsVerify' => 'N',
            ]);

        } else {

            AdmissionUser::create([
                'user_id' => $value,
                'password' => $otp,
                'otp_generated_at' => now(),
                'nar_id' => $registration->nar_id,
                'IsDelete' => 'N',
                'IsVerify' => 'N',
                'special_user' => 'N',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Send OTP Through WhatsApp
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            $message = "Dear Parent,\n";
            $message .= "Your OTP for School Admission is: " . $otp . ".\n";
            $message .= "Please check the school application for more details.\n";
            $message .= '– Evolvu';

            $result = app(WhatsAppService::class)->sendTextMessage(
                $value,
                null,
                [$message]
            );

            Log::info('Admission WhatsApp OTP Response', [
                'phone' => $value,
                'response' => $result
            ]);

            /*
            |--------------------------------------------------------------------------
            | Save WhatsApp Message ID
            |--------------------------------------------------------------------------
            */

            if (
                isset($result['response']['id']) &&
                isset($result['response']['status']) &&
                $result['response']['status'] === 'success'
            ) {

                DB::table('redington_webhook_details')->insert([
                    'wa_id' => $result['response']['id'],
                    'phone_no' => $value,
                    'message_type' => 'admission_otp',
                    'message' => $message,
                    'status' => null,
                    'sms_sent' => 'N',
                    'stu_teacher_id' => null,
                    'notice_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Send OTP Through Email
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'email') {

            $smartMailer = new SmartMailer();

            $smartMailer->send(
                $value,
                 'School Admission OTP - ' . $school->institute_name,
                'emails.admission-otp',
                [
                    'otp' => $otp,
                    'parentName' => $registration->parent_name,
                    'schoolName' => $school->institute_name,
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | API Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'user_type' => 'new',
            'message' => 'OTP generated successfully.',
            'nar_id' => $registration->nar_id,

            // Remove this later in production
            'otp' => $otp,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Verify OTP
    |--------------------------------------------------------------------------
    */

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:mobile,email',
            'value' => 'required|string',
            'otp' => 'required|digits:5',
        ]);

        $type = $validated['type'];
        $value = $validated['value'];
        $otp = $validated['otp'];


        /*
        |--------------------------------------------------------------------------
        | Find Registration
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {
            $registration = Admission::where('phone_no', $value)->first();
        } else {
            $registration = Admission::where('email', $value)->first();
        }


        if (!$registration) {

            return response()->json([
                'success' => false,
                'message' => 'Registration not found.'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Verify OTP
        |--------------------------------------------------------------------------
        */

        $user = AdmissionUser::where('nar_id', $registration->nar_id)
            ->where('user_id', $value)
            ->where('password', $otp)
            ->where('IsDelete', 'N')
            ->first();


        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP.'
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | Mark User Verified
        |--------------------------------------------------------------------------
        */

        $user->update([
            'IsVerify' => 'Y'
        ]);


        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
            'nar_id' => $registration->nar_id,
            'data' => $registration
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Resend OTP
    |--------------------------------------------------------------------------
    */

    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:mobile,email',
            'value' => 'required|string',
            'school_id' => 'required|integer',
        ]);

        $type = $validated['type'];
        $value = $validated['value'];
        $schoolId = $validated['school_id'];

$school = DB::table('school_settings')
    ->where('school_id', $schoolId)
    ->where('is_active', 'Y')
    ->first();

if (!$school) {
    return response()->json([
        'success' => false,
        'message' => 'School not found.'
    ], 404);
}


        /*
        |--------------------------------------------------------------------------
        | Find Registration
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {
            $registration = Admission::where('phone_no', $value)->first();
        } else {
            $registration = Admission::where('email', $value)->first();
        }


        if (!$registration) {

            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Find User Master
        |--------------------------------------------------------------------------
        */

        $user = AdmissionUser::where('nar_id', $registration->nar_id)
            ->where('user_id', $value)
            ->where('IsDelete', 'N')
            ->first();


        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'User account not found.'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate New OTP
        |--------------------------------------------------------------------------
        */

        $otp = rand(10000, 99999);


        /*
        |--------------------------------------------------------------------------
        | Update OTP
        |--------------------------------------------------------------------------
        */

        $user->update([
            'password' => $otp,
            'otp_generated_at' => now(),
            'IsVerify' => 'N',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Resend Through WhatsApp
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            $message = "Dear Parent,\n";
            $message .= "Your new OTP for School Admission is: " . $otp . "\n";
            $message .= "Please use this OTP to continue your admission process.\n";
            $message .= "– Evolvu";

            $result = app(WhatsAppService::class)->sendTextMessage(
                $value,
                null,
                [$message]
            );

            Log::info('Resend WhatsApp OTP Response', [
                'phone' => $value,
                'response' => $result
            ]);


            /*
            |--------------------------------------------------------------------------
            | Save WhatsApp Message ID
            |--------------------------------------------------------------------------
            */

            if (
                isset($result['response']['id']) &&
                isset($result['response']['status']) &&
                $result['response']['status'] === 'success'
            ) {

                DB::table('redington_webhook_details')->insert([
                    'wa_id' => $result['response']['id'],
                    'phone_no' => $value,
                    'message_type' => 'admission_otp',
                    'message' => $message,
                    'status' => null,
                    'sms_sent' => 'N',
                    'stu_teacher_id' => null,
                    'notice_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Resend Through Email
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'email') {

            $smartMailer = new SmartMailer();

            $smartMailer->send(
                $value,
                 'New School Admission OTP - ' . $school->institute_name,
                'emails.admission-otp',
                [
                    'otp' => $otp,
                    'parentName' => $registration->parent_name,
                    'schoolName' => $school->institute_name,
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | API Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' => 'New OTP generated successfully.',
            'nar_id' => $registration->nar_id,

            // Remove this later in production
            'otp' => $otp,
        ]);
    }
    public function getClasses()
{
    $classes = DB::table('class')
        ->select(
            'class_id',
            'name',
            'name_numeric',
            'academic_yr'
        )
        ->orderBy('name')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $classes
    ]);
}
public function getFormFee(Request $request)
{
    $validated = $request->validate([
        'class_id' => 'required|integer',
        'academic_yr' => 'required|string',
        'type' => 'nullable|string',
    ]);

    $query = DB::table('new_admission_class')
        ->where('class_id', $validated['class_id'])
        ->where('academic_yr', $validated['academic_yr'])
        ->where('publish', 'Y');

    if (!empty($validated['type'])) {
        $query->where('type', $validated['type']);
    }

    $admissionClass = $query->first();

    if (!$admissionClass) {
        return response()->json([
            'success' => false,
            'message' => 'Admission form fee not found.'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'class_id' => $admissionClass->class_id,
            'type' => $admissionClass->type,
            'academic_yr' => $admissionClass->academic_yr,
            'form_fee' => $admissionClass->application_form_fee,
        ]
    ]);
}
public function getDashboard(Request $request)
{
    $validated = $request->validate([
        'nar_id' => 'required'
    ]);

    $totalForms = DB::table('online_admission_form')
        ->where('nar_id', $validated['nar_id'])
        ->count();

    return response()->json([
        'success' => true,
        'data' => [
            'forms_count' => $totalForms
        ]
    ]);
}
}