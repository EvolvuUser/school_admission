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
    | IMPORTANT:
    |
    | Existing user -> UPDATE OTP
    | New user      -> CREATE USER
    |
    | The UNIQUE user_id column is handled using UPSERT.
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
                $requestData['value'] = $requestData['email'];

            } elseif (!empty($requestData['mobile'])) {

                $requestData['type'] = 'mobile';
                $requestData['value'] = $requestData['mobile'];

            } elseif (!empty($requestData['phone_no'])) {

                $requestData['type'] = 'mobile';
                $requestData['value'] = $requestData['phone_no'];
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

        /*
         * IMPORTANT:
         *
         * Do not access:
         *
         * $validated['school_id']
         *
         * directly because it is nullable.
         */

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
        | IMPORTANT FIX
        |--------------------------------------------------------------------------
        |
        | DO NOT use:
        |
        | AdmissionUser::create()
        |
        | when user_id already exists.
        |
        | user_id is UNIQUE.
        |
        | We use UPSERT so:
        |
        | Existing user -> UPDATE
        | New user      -> INSERT
        |
        */

        $user =
            $this->createOrUpdateOtpUser(
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
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                true,

            'message' =>
                'OTP generated and sent successfully.',

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
    | CREATE OR UPDATE OTP USER
    |--------------------------------------------------------------------------
    |
    | THIS IS THE MAIN FIX FOR THE DUPLICATE user_id ERROR.
    |
    | MySQL will automatically:
    |
    | INSERT -> if user_id does not exist
    |
    | UPDATE -> if user_id already exists
    |
    */

    private function createOrUpdateOtpUser(
        string $userId,
        int $otp,
        $narId
    ) {

        /*
        |--------------------------------------------------------------------------
        | Check Existing User First
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Do NOT filter by IsDelete here.
        |
        | We need to find ANY existing user with this user_id because
        | user_id is UNIQUE.
        |
        */

        $existingUser =
            AdmissionUser::where(
                'user_id',
                $userId
            )->first();


        /*
        |--------------------------------------------------------------------------
        | Existing User
        |--------------------------------------------------------------------------
        */

        if ($existingUser) {

            /*
             * Update the existing record.
             *
             * This also handles records where IsDelete is Y.
             */

            $existingUser->update([

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
                'Existing Admission User OTP Updated',
                [

                    'user_id' =>
                        $userId,

                    'otp' =>
                        $otp,

                    'nar_id' =>
                        $existingUser->nar_id,

                ]
            );


            return $existingUser;
        }


        /*
        |--------------------------------------------------------------------------
        | New User
        |--------------------------------------------------------------------------
        */

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
        | Use INSERT ... ON DUPLICATE KEY UPDATE
        |--------------------------------------------------------------------------
        |
        | This is safer than simply doing:
        |
        | if (!$user) {
        |     create();
        | }
        |
        | because two requests can arrive at almost the same time.
        |
        */

        DB::table('new_adm_user_master')
            ->upsert(

                [$newUserData],

                ['user_id'],

                [

                    'password',

                    'otp_generated_at',

                    'IsDelete',

                    'IsVerify',

                ]

            );


        /*
        |--------------------------------------------------------------------------
        | Get User After Upsert
        |--------------------------------------------------------------------------
        */

        $user =
            AdmissionUser::where(
                'user_id',
                $userId
            )->first();


        Log::info(
            'Admission User OTP Created/Updated',
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
    | Verify OTP
    |--------------------------------------------------------------------------
    |
    | POST:
    | /api/admission/verify-otp
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
        | Find User
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
        | Mark Verified
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
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Do not create a new user.
        |
        | Update the existing user using the same helper.
        |
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
        | Update Existing OTP
        |--------------------------------------------------------------------------
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


        /*
        |--------------------------------------------------------------------------
        | Send Resend OTP Through WhatsApp
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
        | Send Resend OTP Through Email
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
        | Remove spaces, +, -, brackets, etc.
        */

        $phone =
            preg_replace(
                '/[^0-9]/',
                '',
                $phone
            );


        /*
        | Remove Indian country code
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
        | Validate Indian Mobile
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