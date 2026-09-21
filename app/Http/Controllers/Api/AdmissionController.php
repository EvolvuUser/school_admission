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
    |
    | GET:
    | /api/admission/registration/{narId}
    |
    */

    public function getRegistrationDetails($narId)
    {
        $registration = Admission::where(
            'nar_id',
            $narId
        )->get();

        return response()->json([
            'success' => true,
            'data' => $registration
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Registration
    |--------------------------------------------------------------------------
    |
    | POST:
    | /api/admission/registration
    |
    */

    public function createRegistration(Request $request)
    {
        $validated = $request->validate([
            'parent_name' => 'required|string|max:255',

            'email' => 'nullable|email|max:255',

            'phone_no' => 'nullable|string|max:20',
        ]);


        /*
        |--------------------------------------------------------------------------
        | At least Email OR Mobile is required
        |--------------------------------------------------------------------------
        */

        if (
            empty($validated['email']) &&
            empty($validated['phone_no'])
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'Please provide either email or phone number.'
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Check Existing Registration
        |--------------------------------------------------------------------------
        */

        $registration = null;


        if (!empty($validated['email'])) {

            $registration = Admission::where(
                'email',
                $validated['email']
            )->first();
        }


        if (
            !$registration &&
            !empty($validated['phone_no'])
        ) {

            $registration = Admission::where(
                'phone_no',
                $validated['phone_no']
            )->first();
        }


        /*
        |--------------------------------------------------------------------------
        | Create Registration Only If It Does Not Exist
        |--------------------------------------------------------------------------
        */

        if (!$registration) {

            $registration = Admission::create([

                'parent_name' =>
                    $validated['parent_name'],

                'email' =>
                    $validated['email'] ?? null,

                'phone_no' =>
                    $validated['phone_no'] ?? null,

                'date' =>
                    now()->toDateString(),
            ]);

            $created = true;

        } else {

            $created = false;
        }


        return response()->json([

            'success' => true,

            'message' =>
                $created
                    ? 'Registration created successfully.'
                    : 'Registration already exists.',

            'data' =>
                $registration

        ], $created ? 201 : 200);
    }


    /*
    |--------------------------------------------------------------------------
    | Send OTP
    |--------------------------------------------------------------------------
    |
    | POST:
    | /api/admission/send-otp
    |
    | Existing user:
    |     UPDATE OTP
    |
    | New user:
    |     CREATE USER
    |
    */

    public function sendOtp(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'type' =>
                'required|in:mobile,email',

            'value' =>
                'required|string',

            'parent_name' =>
                'nullable|string|max:255',

            'school_id' =>
                'required|integer',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Get Request Values
        |--------------------------------------------------------------------------
        */

        $type =
            $validated['type'];

        $value =
            trim($validated['value']);

        $schoolId =
            $validated['school_id'];


        /*
        |--------------------------------------------------------------------------
        | Normalize Mobile Number
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            $value = $this->normalizeMobileNumber($value);
        }


        /*
        |--------------------------------------------------------------------------
        | Find School
        |--------------------------------------------------------------------------
        */

        $school =
            DB::table('school_settings')
                ->where(
                    'school_id',
                    $schoolId
                )
                ->where(
                    'is_active',
                    'Y'
                )
                ->first();


        if (!$school) {

            return response()->json([

                'success' => false,

                'message' =>
                    'School not found.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Find Existing Registration
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            $registration =
                Admission::where(
                    'phone_no',
                    $value
                )->first();

        } else {

            $registration =
                Admission::where(
                    'email',
                    $value
                )->first();
        }


        /*
        |--------------------------------------------------------------------------
        | Create Registration If It Does Not Exist
        |--------------------------------------------------------------------------
        */

        if (!$registration) {

            if (
                empty(
                    $validated['parent_name']
                )
            ) {

                return response()->json([

                    'success' => false,

                    'message' =>
                        'Parent name is required for new registration.'

                ], 422);
            }


            $registration =
                Admission::create([

                    'parent_name' =>
                        $validated['parent_name'],

                    'email' =>
                        $type === 'email'
                            ? $value
                            : null,

                    'phone_no' =>
                        $type === 'mobile'
                            ? $value
                            : null,

                    'date' =>
                        now()->toDateString(),
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate OTP
        |--------------------------------------------------------------------------
        */

        $otp =
            random_int(
                10000,
                99999
            );


        /*
        |--------------------------------------------------------------------------
        | Find User Master
        |--------------------------------------------------------------------------
        |
        | user_id is UNIQUE.
        |
        | Therefore:
        |
        | Existing user -> UPDATE
        | New user      -> CREATE
        |
        */

        $user =
            AdmissionUser::where(
                'user_id',
                $value
            )
            ->where(
                'IsDelete',
                'N'
            )
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Existing User -> UPDATE OTP
        |--------------------------------------------------------------------------
        */

        if ($user) {

            $user->update([

                'password' =>
                    $otp,

                'otp_generated_at' =>
                    now(),

                'IsVerify' =>
                    'N',
            ]);

            $userType = 'existing';

        }


        /*
        |--------------------------------------------------------------------------
        | New User -> CREATE
        |--------------------------------------------------------------------------
        */

        else {

            $user =
                AdmissionUser::create([

                    'user_id' =>
                        $value,

                    'password' =>
                        $otp,

                    'otp_generated_at' =>
                        now(),

                    'nar_id' =>
                        $registration->nar_id,

                    'IsDelete' =>
                        'N',

                    'IsVerify' =>
                        'N',

                    'special_user' =>
                        'N',
                ]);

            $userType = 'new';
        }


        /*
        |--------------------------------------------------------------------------
        | Send OTP Through WhatsApp
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            $whatsappResult =
                $this->sendAdmissionWhatsappOtp(
                    $value,
                    $otp,
                    'send'
                );


            /*
            |--------------------------------------------------------------------------
            | Check WhatsApp Result
            |--------------------------------------------------------------------------
            */

            if (
                !$whatsappResult['success']
            ) {

                Log::error(
                    'Admission WhatsApp OTP Failed',
                    [
                        'phone' =>
                            $value,

                        'otp' =>
                            $otp,

                        'response' =>
                            $whatsappResult['raw_response']
                    ]
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Send OTP Through Email
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'email') {

            $smartMailer =
                new SmartMailer();


            $smartMailer->send(

                $value,

                'School Admission OTP - '
                    . $school->institute_name,

                'emails.admission-otp',

                [

                    'otp' =>
                        $otp,

                    'parentName' =>
                        $registration->parent_name,

                    'schoolName' =>
                        $school->institute_name,

                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | API Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                true,

            'user_type' =>
                $userType,

            'message' =>
                $userType === 'existing'
                    ? 'Existing user found. OTP updated successfully.'
                    : 'New user created and OTP generated successfully.',

            'nar_id' =>
                $registration->nar_id,

            /*
            |--------------------------------------------------------------------------
            | Remove OTP From Response In Production
            |--------------------------------------------------------------------------
            */

            'otp' =>
                $otp,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Verify OTP
    |--------------------------------------------------------------------------
    |
    | POST:
    | /api/admission/verify-otp
    |
    */

    public function verifyOtp(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([

                'type' =>
                    'required|in:mobile,email',

                'value' =>
                    'required|string',

                'otp' =>
                    'required|digits:5',
            ]);


        $type =
            $validated['type'];

        $value =
            trim($validated['value']);

        $otp =
            $validated['otp'];


        /*
        |--------------------------------------------------------------------------
        | Normalize Mobile
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            $value =
                $this->normalizeMobileNumber(
                    $value
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Find Registration
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            $registration =
                Admission::where(
                    'phone_no',
                    $value
                )->first();

        } else {

            $registration =
                Admission::where(
                    'email',
                    $value
                )->first();
        }


        if (!$registration) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Registration not found.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Find User Master
        |--------------------------------------------------------------------------
        */

        $user =
            AdmissionUser::where(
                'user_id',
                $value
            )
            ->where(
                'password',
                $otp
            )
            ->where(
                'IsDelete',
                'N'
            )
            ->where(
                'IsVerify',
                'N'
            )
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Invalid OTP
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Invalid OTP.'

            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | Mark User As Verified
        |--------------------------------------------------------------------------
        */

        $user->update([

            'IsVerify' =>
                'Y'

        ]);


        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                true,

            'message' =>
                'OTP verified successfully.',

            'nar_id' =>
                $registration->nar_id,

            'data' =>
                $registration

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | RESEND OTP
    |--------------------------------------------------------------------------
    |
    | POST:
    | /api/admission/resend-otp
    |
    */

    public function resendOtp(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([

                'type' =>
                    'required|in:mobile,email',

                'value' =>
                    'required|string',

                'school_id' =>
                    'required|integer',
            ]);


        $type =
            $validated['type'];

        $value =
            trim($validated['value']);

        $schoolId =
            $validated['school_id'];


        /*
        |--------------------------------------------------------------------------
        | Normalize Mobile Number
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            $value =
                $this->normalizeMobileNumber(
                    $value
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Find School
        |--------------------------------------------------------------------------
        */

        $school =
            DB::table('school_settings')
                ->where(
                    'school_id',
                    $schoolId
                )
                ->where(
                    'is_active',
                    'Y'
                )
                ->first();


        if (!$school) {

            return response()->json([

                'success' => false,

                'message' =>
                    'School not found.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Find Registration
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            $registration =
                Admission::where(
                    'phone_no',
                    $value
                )->first();

        } else {

            $registration =
                Admission::where(
                    'email',
                    $value
                )->first();
        }


        if (!$registration) {

            return response()->json([

                'success' => false,

                'message' =>
                    'User registration not found.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Find Existing User
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | user_id is UNIQUE.
        |
        | Search by user_id only.
        |
        */

        $user =
            AdmissionUser::where(
                'user_id',
                $value
            )
            ->where(
                'IsDelete',
                'N'
            )
            ->first();


        if (!$user) {

            return response()->json([

                'success' => false,

                'message' =>
                    'User account not found.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate New OTP
        |--------------------------------------------------------------------------
        */

        $otp =
            random_int(
                10000,
                99999
            );


        /*
        |--------------------------------------------------------------------------
        | Update Existing OTP
        |--------------------------------------------------------------------------
        */

        $user->update([

            'password' =>
                $otp,

            'otp_generated_at' =>
                now(),

            'IsVerify' =>
                'N',
        ]);


        /*
        |--------------------------------------------------------------------------
        | RESEND THROUGH WHATSAPP
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |--------------------------------------------------------------------------
            |
            | Use the SAME message format as sendOtp().
            |
            | This avoids a different WhatsApp message format being used
            | for resend.
            |
            */

            $whatsappResult =
                $this->sendAdmissionWhatsappOtp(
                    $value,
                    $otp,
                    'resend'
                );


            /*
            |--------------------------------------------------------------------------
            | WhatsApp Failed
            |--------------------------------------------------------------------------
            */

            if (
                !$whatsappResult['success']
            ) {

                Log::error(
                    'Resend WhatsApp OTP Failed',
                    [
                        'phone' =>
                            $value,

                        'otp' =>
                            $otp,

                        'response' =>
                            $whatsappResult['raw_response']
                    ]
                );


                return response()->json([

                    'success' =>
                        false,

                    'message' =>
                        'OTP was generated but WhatsApp delivery failed.',

                    'nar_id' =>
                        $registration->nar_id,

                ], 500);
            }


            /*
            |--------------------------------------------------------------------------
            | WhatsApp Accepted
            |--------------------------------------------------------------------------
            */

            Log::info(
                'Resend WhatsApp OTP Successfully Accepted',
                [

                    'phone' =>
                        $value,

                    'otp' =>
                        $otp,

                    'message_id' =>
                        $whatsappResult['message_id'],

                    'provider_response' =>
                        $whatsappResult['provider_response'],

                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | RESEND THROUGH EMAIL
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'email') {

            $smartMailer =
                new SmartMailer();


            $smartMailer->send(

                $value,

                'New School Admission OTP - '
                    . $school->institute_name,

                'emails.admission-otp',

                [

                    'otp' =>
                        $otp,

                    'parentName' =>
                        $registration->parent_name,

                    'schoolName' =>
                        $school->institute_name,

                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | API Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                true,

            'message' =>
                'New OTP generated and sent successfully.',

            'nar_id' =>
                $registration->nar_id,

            /*
            |--------------------------------------------------------------------------
            | Remove OTP From Response In Production
            |--------------------------------------------------------------------------
            */

            'otp' =>
                $otp,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SEND ADMISSION WHATSAPP OTP
    |--------------------------------------------------------------------------
    |
    | Common function used by:
    |
    | 1. sendOtp()
    | 2. resendOtp()
    |
    | This is important because both OTP operations now use exactly
    | the same WhatsApp sending process.
    |
    */

    private function sendAdmissionWhatsappOtp(
        string $phone,
        int $otp,
        string $action = 'send'
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Normalize Phone
        |--------------------------------------------------------------------------
        */

        $phone =
            $this->normalizeMobileNumber(
                $phone
            );


        /*
        |--------------------------------------------------------------------------
        | Use SAME message format for Send + Resend
        |--------------------------------------------------------------------------
        */

        $message =
            "Dear Parent,\n";

        $message .=
            "Your OTP for School Admission is: "
            . $otp
            . ".\n";

        $message .=
            "Please check the school application for more details.\n";

        $message .=
            "– Evolvu";


        /*
        |--------------------------------------------------------------------------
        | Send Through WhatsApp Service
        |--------------------------------------------------------------------------
        */

        try {

            $result =
                app(
                    WhatsAppService::class
                )->sendTextMessage(

                    $phone,

                    null,

                    [$message]
                );


        } catch (\Throwable $e) {

            Log::error(
                'WhatsApp OTP Exception',
                [

                    'action' =>
                        $action,

                    'phone' =>
                        $phone,

                    'otp' =>
                        $otp,

                    'error' =>
                        $e->getMessage(),

                ]
            );


            return [

                'success' =>
                    false,

                'message_id' =>
                    null,

                'provider_response' =>
                    null,

                'raw_response' =>
                    $e->getMessage(),

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Log Complete Raw Response
        |--------------------------------------------------------------------------
        */

        Log::info(
            'WhatsApp OTP Provider Response',
            [

                'action' =>
                    $action,

                'phone' =>
                    $phone,

                'otp' =>
                    $otp,

                'response' =>
                    $result,

            ]
        );


        /*
        |--------------------------------------------------------------------------
        | IMPORTANT:
        |--------------------------------------------------------------------------
        |
        | Your actual response is:
        |
        | $result['response']['response']
        |
        | Example:
        |
        | [
        |     'response' => [
        |         'response' => [
        |             'phone' => '919422512735',
        |             'id' => '...',
        |             'status' => 'success'
        |         ]
        |     ]
        | ]
        |
        | Therefore we handle BOTH possible response structures.
        |
        */

        $providerResponse =
            $result['response']['response']
            ?? $result['response']
            ?? $result;


        /*
        |--------------------------------------------------------------------------
        | Extract Status
        |--------------------------------------------------------------------------
        */

        $status =
            $providerResponse['status']
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | Extract WhatsApp Message ID
        |--------------------------------------------------------------------------
        */

        $messageId =
            $providerResponse['id']
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | Check Provider Success
        |--------------------------------------------------------------------------
        */

        $success =
            (
                $status === 'success' &&
                !empty($messageId)
            );


        /*
        |--------------------------------------------------------------------------
        | Save WhatsApp Message Log
        |--------------------------------------------------------------------------
        */

        if ($success) {

            try {

                DB::table(
                    'redington_webhook_details'
                )->insert([

                    'wa_id' =>
                        $messageId,

                    'phone_no' =>
                        $phone,

                    'message_type' =>
                        'admission_otp',

                    'message' =>
                        $message,

                    /*
                     * Delivery status will be updated
                     * later by webhook if available.
                     */

                    'status' =>
                        null,

                    'sms_sent' =>
                        'N',

                    'stu_teacher_id' =>
                        null,

                    'notice_id' =>
                        null,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),

                ]);

            } catch (\Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | Do not fail OTP sending if logging fails.
                |--------------------------------------------------------------------------
                */

                Log::error(
                    'WhatsApp OTP Log Insert Failed',
                    [

                        'phone' =>
                            $phone,

                        'message_id' =>
                            $messageId,

                        'error' =>
                            $e->getMessage(),

                    ]
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Return Result
        |--------------------------------------------------------------------------
        */

        return [

            'success' =>
                $success,

            'message_id' =>
                $messageId,

            'provider_response' =>
                $providerResponse,

            'raw_response' =>
                $result,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Mobile Number
    |--------------------------------------------------------------------------
    |
    | Converts:
    |
    | 9422512735
    | +919422512735
    | 919422512735
    |
    | into:
    |
    | 9422512735
    |
    | The WhatsApp service can then apply its own country-code logic.
    |
    */

    private function normalizeMobileNumber(
        string $phone
    ): string {

        $phone =
            trim($phone);


        /*
        | Remove spaces, +, -, brackets, etc.
        */

        $phone =
            preg_replace(
                '/[^0-9]/',
                '',
                $phone
            );


        /*
        |--------------------------------------------------------------------------
        | Remove Indian country code if supplied
        |--------------------------------------------------------------------------
        */

        if (
            strlen($phone) === 12 &&
            substr($phone, 0, 2) === '91'
        ) {

            $phone =
                substr(
                    $phone,
                    2
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Indian Mobile Number
        |--------------------------------------------------------------------------
        */

        if (
            strlen($phone) !== 10 ||
            !preg_match(
                '/^[6-9][0-9]{9}$/',
                $phone
            )
        ) {

            throw new \InvalidArgumentException(
                'Invalid mobile number.'
            );
        }


        return $phone;
    }


    /*
    |--------------------------------------------------------------------------
    | Get Classes
    |--------------------------------------------------------------------------
    |
    | GET:
    | /api/admission/classes
    |
    */

    public function getClasses()
    {
        $classes =
            DB::table('class')
                ->join(
                    'new_admission_class',
                    function ($join) {

                        $join->on(
                            'class.class_id',
                            '=',
                            'new_admission_class.class_id'
                        );

                        $join->on(
                            'class.academic_yr',
                            '=',
                            'new_admission_class.academic_yr'
                        );
                    }
                )
                ->where(
                    'new_admission_class.publish',
                    'Y'
                )
                ->select(

                    'class.class_id',

                    'class.name',

                    'class.name_numeric',

                    'class.academic_yr',

                    'new_admission_class.application_form_fee'

                )
                ->orderBy(
                    'class.name'
                )
                ->get();


        return response()->json([

            'success' =>
                true,

            'data' =>
                $classes

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Get Admission Form Fee
    |--------------------------------------------------------------------------
    |
    | GET:
    | /api/admission/form-fee
    |
    */

    public function getFormFee(
        Request $request
    ) {

        $validated =
            $request->validate([

                'class_id' =>
                    'required|integer',

                'academic_yr' =>
                    'required|string',

                'type' =>
                    'nullable|string',

            ]);


        $query =
            DB::table(
                'new_admission_class'
            )
            ->where(
                'class_id',
                $validated['class_id']
            )
            ->where(
                'academic_yr',
                $validated['academic_yr']
            )
            ->where(
                'publish',
                'Y'
            );


        if (
            !empty(
                $validated['type']
            )
        ) {

            $query->where(
                'type',
                $validated['type']
            );
        }


        $admissionClass =
            $query->first();


        if (!$admissionClass) {

            return response()->json([

                'success' =>
                    false,

                'message' =>
                    'Admission form fee not found.'

            ], 404);
        }


        return response()->json([

            'success' =>
                true,

            'data' => [

                'class_id' =>
                    $admissionClass->class_id,

                'type' =>
                    $admissionClass->type,

                'academic_yr' =>
                    $admissionClass->academic_yr,

                'form_fee' =>
                    $admissionClass->application_form_fee,

            ]

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Get Dashboard
    |--------------------------------------------------------------------------
    |
    | GET:
    | /api/admission/dashboard
    |
    */

    public function getDashboard(
        Request $request
    ) {

        $validated =
            $request->validate([

                'nar_id' =>
                    'required'

            ]);


        $totalForms =
            DB::table(
                'online_admission_form'
            )
            ->where(
                'nar_id',
                $validated['nar_id']
            )
            ->count();


        return response()->json([

            'success' =>
                true,

            'data' => [

                'forms_count' =>
                    $totalForms

            ]

        ]);
    }
}