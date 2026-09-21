<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\AdmissionUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Services\WhatsAppService;
use App\Http\Services\SmartMailer;
use Carbon\Carbon;

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
    | Send OTP / Login
    |--------------------------------------------------------------------------
    |
    | POST:
    | /api/admission/send-otp
    |
    | IMPORTANT:
    |
    | NEW USER:
    |     Generate first OTP.
    |
    | EXISTING USER:
    |     DO NOT generate a new OTP.
    |     DO NOT update password.
    |     DO NOT send WhatsApp OTP.
    |
    | The existing user's current password/OTP remains unchanged.
    |
    */

    public function sendOtp(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Prepare Request
        |--------------------------------------------------------------------------
        */

        $requestData = $request->all();


        /*
        |--------------------------------------------------------------------------
        | Detect Type Automatically
        |--------------------------------------------------------------------------
        */

        if (empty($requestData['type'])) {

            if (!empty($requestData['email'])) {

                $requestData['type'] = 'email';

                $requestData['value'] =
                    $requestData['email'];

            } elseif (!empty($requestData['mobile'])) {

                $requestData['type'] = 'mobile';

                $requestData['value'] =
                    $requestData['mobile'];

            } elseif (!empty($requestData['phone_no'])) {

                $requestData['type'] = 'mobile';

                $requestData['value'] =
                    $requestData['phone_no'];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Detect Value
        |--------------------------------------------------------------------------
        */

        if (
            empty($requestData['value']) &&
            !empty($requestData['email'])
        ) {

            $requestData['value'] =
                $requestData['email'];
        }


        if (
            empty($requestData['value']) &&
            !empty($requestData['mobile'])
        ) {

            $requestData['value'] =
                $requestData['mobile'];
        }


        if (
            empty($requestData['value']) &&
            !empty($requestData['phone_no'])
        ) {

            $requestData['value'] =
                $requestData['phone_no'];
        }


        $request->replace($requestData);


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
                'nullable|integer',

        ]);


        /*
        |--------------------------------------------------------------------------
        | Get Values
        |--------------------------------------------------------------------------
        */

        $type =
            $validated['type'];

        $value =
            trim($validated['value']);

        $schoolId =
            $validated['school_id'] ?? null;


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
        | Find School
        |--------------------------------------------------------------------------
        */

        $school = null;

        if ($schoolId !== null) {

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
        | CHECK EXISTING USER
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | This is the main change.
        |
        | If the user already exists:
        |
        | - Do NOT generate OTP
        | - Do NOT update password
        | - Do NOT send WhatsApp
        |
        | The user should use the existing OTP/password.
        |
        */

        $existingUser =
            AdmissionUser::where(
                'user_id',
                $value
            )->first();


        if ($existingUser) {

            Log::info(
                'Existing Admission User Login - Existing Password Will Be Used',
                [

                    'user_id' =>
                        $value,

                    'nar_id' =>
                        $existingUser->nar_id,

                    'IsVerify' =>
                        $existingUser->IsVerify,

                ]
            );


            return response()->json([

                'success' =>
                    true,

                'message' =>
                    'Existing user found. Please use your existing OTP/password.',

                'is_existing_user' =>
                    true,

                'otp_sent' =>
                    false,

                'nar_id' =>
                    $existingUser->nar_id,

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | NEW USER
        |--------------------------------------------------------------------------
        |
        | Only a NEW user receives a first OTP.
        |
        */

        $otp =
            random_int(
                10000,
                99999
            );


        /*
        |--------------------------------------------------------------------------
        | Create New OTP User
        |--------------------------------------------------------------------------
        */

        $user =
            $this->createNewOtpUser(
                $value,
                $otp,
                $registration->nar_id
            );


        /*
        |--------------------------------------------------------------------------
        | Send WhatsApp OTP
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            $whatsappResult =
                $this->sendAdmissionWhatsappOtp(
                    $value,
                    $otp,
                    'send'
                );


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
        | Send Email OTP
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'email') {

            $smartMailer =
                new SmartMailer();


            $smartMailer->send(

                $value,

                'School Admission OTP - '
                    . (
                        $school->institute_name
                        ?? 'School'
                    ),

                'emails.admission-otp',

                [

                    'otp' =>
                        $otp,

                    'parentName' =>
                        $registration->parent_name,

                    'schoolName' =>
                        $school->institute_name
                        ?? 'School',

                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Response For NEW User
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                true,

            'message' =>
                'OTP generated and sent successfully.',

            'is_existing_user' =>
                false,

            'otp_sent' =>
                true,

            'nar_id' =>
                $registration->nar_id,

            /*
             * Remove this in production.
             */
            'otp' =>
                $otp,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE NEW OTP USER
    |--------------------------------------------------------------------------
    |
    | This method is ONLY used for a new user.
    |
    | Existing users are NOT updated here.
    |
    */

    private function createNewOtpUser(
        string $userId,
        int $otp,
        $narId
    ) {

        $newUserData = [

            'user_id' =>
                $userId,

            'password' =>
                $otp,

            'otp_generated_at' =>
                now(),

            'nar_id' =>
                $narId,

            'IsDelete' =>
                'N',

            'IsVerify' =>
                'N',

            'special_user' =>
                'N',

        ];


        /*
        |--------------------------------------------------------------------------
        | Insert Only
        |--------------------------------------------------------------------------
        |
        | user_id is UNIQUE.
        |
        | insertOrIgnore prevents duplicate-entry error if another request
        | creates the same user at nearly the same time.
        |
        */

        DB::table('new_adm_user_master')
            ->insertOrIgnore(
                $newUserData
            );


        /*
        |--------------------------------------------------------------------------
        | Get User
        |--------------------------------------------------------------------------
        */

        $user =
            AdmissionUser::where(
                'user_id',
                $userId
            )->first();


        Log::info(
            'New Admission User OTP Created',
            [

                'user_id' =>
                    $userId,

                'otp' =>
                    $otp,

                'nar_id' =>
                    $user
                        ? $user->nar_id
                        : $narId,

            ]
        );


        return $user;
    }


    /*
    |--------------------------------------------------------------------------
    | Verify OTP / Password
    |--------------------------------------------------------------------------
    |
    | POST:
    | /api/admission/verify-otp
    |
    | IMPORTANT:
    |
    | Existing verified users must also be allowed to login using their
    | permanent password.
    |
    | Therefore we DO NOT require:
    |
    | IsVerify = N
    |
    | anymore.
    |
    */

    public function verifyOtp(Request $request)
    {
        $requestData = $request->all();


        /*
        |--------------------------------------------------------------------------
        | Detect Type
        |--------------------------------------------------------------------------
        */

        if (empty($requestData['type'])) {

            if (!empty($requestData['email'])) {

                $requestData['type'] = 'email';

                $requestData['value'] =
                    $requestData['email'];

            } elseif (!empty($requestData['mobile'])) {

                $requestData['type'] = 'mobile';

                $requestData['value'] =
                    $requestData['mobile'];

            } elseif (!empty($requestData['phone_no'])) {

                $requestData['type'] = 'mobile';

                $requestData['value'] =
                    $requestData['phone_no'];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Detect Value
        |--------------------------------------------------------------------------
        */

        if (
            empty($requestData['value']) &&
            !empty($requestData['email'])
        ) {

            $requestData['value'] =
                $requestData['email'];
        }


        if (
            empty($requestData['value']) &&
            !empty($requestData['mobile'])
        ) {

            $requestData['value'] =
                $requestData['mobile'];
        }


        if (
            empty($requestData['value']) &&
            !empty($requestData['phone_no'])
        ) {

            $requestData['value'] =
                $requestData['phone_no'];
        }


        $request->replace($requestData);


        /*
        |--------------------------------------------------------------------------
        | Validate
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
        | Find User By Current Password
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | We only check:
        |
        | user_id
        | password
        | IsDelete = N
        |
        | We do NOT check IsVerify = N.
        |
        | Therefore:
        |
        | First OTP -> works
        | Permanent password -> works
        | Resent OTP -> new password works
        |
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
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Invalid OTP / Password
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Invalid OTP/password.'

            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | Mark User Verified
        |--------------------------------------------------------------------------
        |
        | If the user is already verified, this simply keeps it as Y.
        |
        */

        if ($user->IsVerify !== 'Y') {

            $user->update([

                'IsVerify' =>
                    'Y'

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                true,

            'message' =>
                'OTP/password verified successfully.',

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
    | IMPORTANT:
    |
    | This is the ONLY method that generates a new OTP for an existing user.
    |
    | New OTP:
    |
    | 1. Generate
    | 2. Replace password in new_adm_user_master
    | 3. Update otp_generated_at
    | 4. Set IsVerify = N
    | 5. Send WhatsApp/Email
    |
    */

    public function resendOtp(Request $request)
    {
        $requestData = $request->all();


        /*
        |--------------------------------------------------------------------------
        | Detect Type
        |--------------------------------------------------------------------------
        */

        if (empty($requestData['type'])) {

            if (!empty($requestData['email'])) {

                $requestData['type'] = 'email';

                $requestData['value'] =
                    $requestData['email'];

            } elseif (!empty($requestData['mobile'])) {

                $requestData['type'] = 'mobile';

                $requestData['value'] =
                    $requestData['mobile'];

            } elseif (!empty($requestData['phone_no'])) {

                $requestData['type'] = 'mobile';

                $requestData['value'] =
                    $requestData['phone_no'];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Detect Value
        |--------------------------------------------------------------------------
        */

        if (
            empty($requestData['value']) &&
            !empty($requestData['email'])
        ) {

            $requestData['value'] =
                $requestData['email'];
        }


        if (
            empty($requestData['value']) &&
            !empty($requestData['mobile'])
        ) {

            $requestData['value'] =
                $requestData['mobile'];
        }


        if (
            empty($requestData['value']) &&
            !empty($requestData['phone_no'])
        ) {

            $requestData['value'] =
                $requestData['phone_no'];
        }


        $request->replace($requestData);


        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([

                'type' =>
                    'required|in:mobile,email',

                'value' =>
                    'required|string',

                'school_id' =>
                    'nullable|integer',

            ]);


        $type =
            $validated['type'];

        $value =
            trim($validated['value']);

        $schoolId =
            $validated['school_id'] ?? null;


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
        | Find School
        |--------------------------------------------------------------------------
        */

        $school = null;

        if ($schoolId !== null) {

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
        */

        $user =
            AdmissionUser::where(
                'user_id',
                $value
            )->first();


        if (!$user) {

            return response()->json([

                'success' => false,

                'message' =>
                    'User account not found.'

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate NEW OTP
        |--------------------------------------------------------------------------
        */

        $otp =
            random_int(
                10000,
                99999
            );


        /*
        |--------------------------------------------------------------------------
        | Update Existing User
        |--------------------------------------------------------------------------
        |
        | This replaces the old OTP/password.
        |
        */

        $user->update([

            'password' =>
                $otp,

            'otp_generated_at' =>
                now(),

            'IsDelete' =>
                'N',

            'IsVerify' =>
                'N',

        ]);


        Log::info(
            'Admission Resend OTP - Password Updated',
            [

                'user_id' =>
                    $value,

                'otp' =>
                    $otp,

                'nar_id' =>
                    $user->nar_id,

            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Send WhatsApp OTP
        |--------------------------------------------------------------------------
        */

        if ($type === 'mobile') {

            $whatsappResult =
                $this->sendAdmissionWhatsappOtp(
                    $value,
                    $otp,
                    'resend'
                );


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
                        'OTP generated but WhatsApp delivery failed.',

                    'nar_id' =>
                        $registration->nar_id,

                ], 500);
            }


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
        | Send Email OTP
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'email') {

            $smartMailer =
                new SmartMailer();


            $smartMailer->send(

                $value,

                'New School Admission OTP - '
                    . (
                        $school->institute_name
                        ?? 'School'
                    ),

                'emails.admission-otp',

                [

                    'otp' =>
                        $otp,

                    'parentName' =>
                        $registration->parent_name,

                    'schoolName' =>
                        $school->institute_name
                        ?? 'School',

                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                true,

            'message' =>
                'New OTP generated and sent successfully.',

            'is_existing_user' =>
                true,

            'otp_sent' =>
                true,

            'nar_id' =>
                $registration->nar_id,

            /*
             * Remove this in production.
             */
            'otp' =>
                $otp,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SEND ADMISSION WHATSAPP OTP
    |--------------------------------------------------------------------------
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
        | WhatsApp Message
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
        | Send WhatsApp
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
        | Log Provider Response
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
        | Extract Provider Response
        |--------------------------------------------------------------------------
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
        | Extract Message ID
        |--------------------------------------------------------------------------
        */

        $messageId =
            $providerResponse['id']
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | Check Success
        |--------------------------------------------------------------------------
        */

        $success =
            (
                $status === 'success' &&
                !empty($messageId)
            );


        /*
        |--------------------------------------------------------------------------
        | Save WhatsApp Log
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
    */

    private function normalizeMobileNumber(
        string $phone
    ): string {

        $phone =
            trim($phone);


        /*
        |--------------------------------------------------------------------------
        | Remove spaces, +, -, brackets, etc.
        |--------------------------------------------------------------------------
        */

        $phone =
            preg_replace(
                '/[^0-9]/',
                '',
                $phone
            );


        /*
        |--------------------------------------------------------------------------
        | Remove Indian country code
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
        | Validate Indian Mobile
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

                'success' => false,

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