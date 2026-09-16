<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnlineAdmissionFee;
use App\Models\OnlineAdmissionForm;
use App\Models\NewAdmissionClass;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\WorldlineService;

class AdmissionPaymentController extends Controller
{
    public function createPayment(
        Request $request,
        WorldlineService $worldlineService
    ) {
        // 1. Validate form_id
        $validated = $request->validate([
            'form_id' => 'required|string|max:50|exists:online_admission_form,form_id',
            'nar_id' => 'required|integer',
        ]);

        // 2. Find admission form
        $admissionForm = OnlineAdmissionForm::where(
            'form_id',
            $validated['form_id']
        )->first();

        if (!$admissionForm) {
            return response()->json([
                'success' => false,
                'message' => 'Admission form not found.',
            ], 404);
        }

        if ((int) $admissionForm->nar_id !== (int) $validated['nar_id']) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to pay for this admission form.',
            ], 403);
        }

        // 3. Check if payment is already completed
        $successfulPayment = OnlineAdmissionFee::where(
            'form_id',
            $admissionForm->form_id
        )
        ->where('status', 'S')
        ->first();

        if ($successfulPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment has already been completed for this admission form.',
                'data' => [
                    'form_id' => $admissionForm->form_id,
                    'order_id' => $successfulPayment->OrderId,
                    'amount' => $successfulPayment->amount,
                    'status' => $successfulPayment->status,
                ],
            ], 409);
        }

        // 4. Find class + academic year
        $admissionClass = NewAdmissionClass::where(
            'class_id',
            $admissionForm->class_id
        )
        ->where(
            'academic_yr',
            $admissionForm->academic_yr
        )
        ->where('publish', 'Y')
        ->first();

        if (!$admissionClass) {
            return response()->json([
                'success' => false,
                'message' => 'Admission class information not found.',
            ], 404);
        }

        // 5. Fetch application form fee
        $amount = $admissionClass->application_form_fee;

        // 6. Generate unique OrderId
        do {
            $orderId = 'ADM' . now()->format('ymdHis') . strtoupper(
                Str::random(4)
            );
        } while (
            OnlineAdmissionFee::where('OrderId', $orderId)->exists()
        );

        // 7. Insert payment attempt
        $payment = OnlineAdmissionFee::create([
            'OrderId'      => $orderId,
            'status'       => 'A',
            'form_id'      => $admissionForm->form_id,

            'first_name'   => $admissionForm->first_name,
            'last_name'    => $admissionForm->last_name ?? '',

            'parent_name'  => $admissionForm->father_name
                              ?? $admissionForm->mother_name
                              ?? '',

            'phone'        => $admissionForm->sms_sending_phone_no,

            'email'        => $admissionForm->f_email
                              ?? $admissionForm->m_emailid
                              ?? '',

            'remark'       => 'Admission Form Fee',
            'payment_date' => now()->toDateString(),
            'amount'       => $amount,

            // Worldline transaction reference will be updated
            // after the payment callback.
            'Trnx_ref_no'  => 0,

            'rrn'          => null,

            'Status_code'  => 'A',
            'Status_desc'  => 'Payment Attempted',
            'synced_later' => 'N',

            'academic_yr'  => $admissionForm->academic_yr,
        ]);

        // 8. Send payment request to Worldline
        try {
            $worldlineResponse = $worldlineService->createPayment([
                'amount'   => $payment->amount,
                'order_id' => $payment->OrderId,
                'phone'    => $payment->phone,
                'email'    => $payment->email,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to initialize payment gateway.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        // 9. Return payment information
        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully.',
            'data' => [
                'payment_id'   => $payment->adfees_payment_id,
                'form_id'      => $payment->form_id,
                'order_id'     => $payment->OrderId,
                'amount'       => $payment->amount,
                'currency'     => 'INR',
                'status'       => $payment->status,
                'academic_yr'  => $payment->academic_yr,
                'class_id'     => $admissionForm->class_id,

                // Worldline bank/payment page URL
                'bank_acs_url' => $worldlineResponse['bank_acs_url'],
            ],
        ], 201);
    }

    /**
 * Get payment status by Order ID.
 *
 * GET:
 * /api/admission/payment/{orderId}
 */
public function paymentStatus(Request $request, $orderId)
{
    $validated = $request->validate([
        'nar_id' => 'required|integer',
    ]);

    $payment = OnlineAdmissionFee::where(
        'OrderId',
        $orderId
    )->first();

    if (!$payment) {
        return response()->json([
            'success' => false,
            'message' => 'Payment record not found.',
        ], 404);
    }

    $admissionForm = OnlineAdmissionForm::where(
        'form_id',
        $payment->form_id
    )->first();

    if (!$admissionForm || (int) $admissionForm->nar_id !== (int) $validated['nar_id']) {
        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to view this payment.',
        ], 403);
    }

    return response()->json([
        'success' => true,
        'message' => 'Payment status fetched successfully.',
        'data' => [
            'payment_id' => $payment->adfees_payment_id,
            'order_id' => $payment->OrderId,
            'form_id' => $payment->form_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'status_description' => $payment->Status_desc,
            'transaction_reference' => $payment->Trnx_ref_no,
            'rrn' => $payment->rrn,
            'payment_date' => $payment->payment_date,
            'academic_yr' => $payment->academic_yr,
        ],
    ]);
}


    /**
     * Handle Worldline payment callback.
     */
    public function paymentCallback(Request $request)
    {
        // Worldline sends the encrypted response in "msg".
        $encryptedMessage = $request->input('msg');

        if (!$encryptedMessage) {
            return response()->json([
                'success' => false,
                'message' => 'Payment callback message is missing.',
            ], 400);
        }

        try {
            // Get Worldline credentials from config.
            $key = config('payment.worldline.key');
            $iv  = config('payment.worldline.iv');

            if (!$key || !$iv) {
                throw new \Exception(
                    'Worldline configuration is missing.'
                );
            }

            // Decrypt Worldline callback.
            $encryptedData = hex2bin($encryptedMessage);

            if ($encryptedData === false) {
                throw new \Exception(
                    'Invalid hexadecimal callback data.'
                );
            }

            $decryptedData = openssl_decrypt(
                $encryptedData,
                'aes-128-cbc',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decryptedData === false) {
                throw new \Exception(
                    'Unable to decrypt Worldline callback.'
                );
            }

            // Convert decrypted JSON into an array.
            $callbackData = json_decode(
                $decryptedData,
                true
            );

            if (!is_array($callbackData)) {
                throw new \Exception(
                    'Invalid JSON received from Worldline.'
                );
            }

            // Get OrderId from Worldline response.
            $orderId = $callbackData[
                'merchantTransactionIdentifier'
            ] ?? null;

            if (!$orderId) {
                throw new \Exception(
                    'Order ID not found in Worldline callback.'
                );
            }

            // Find payment record.
            $payment = OnlineAdmissionFee::where(
                'OrderId',
                $orderId
            )->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found.',
                    'order_id' => $orderId,
                ], 404);
            }

            // Get payment status information.
            $status = $callbackData[
                'paymentMethod'
            ]['paymentTransaction']['statusMessage'] ?? '';

            $reference = $callbackData[
                'paymentMethod'
            ]['paymentTransaction']['reference'] ?? null;

            /*
             * Worldline successful response from the old
             * CodeIgniter implementation.
             */
            $isSuccess = strtolower($status) === 'success';

            if ($isSuccess) {

                // Update payment as successful.
                $payment->update([
                    'status'      => 'S',
                    'Trnx_ref_no' => $reference ?? 0,
                    'Status_code' => 'S',
                    'Status_desc' => $status,
                ]);

                // Update admission form status.
                OnlineAdmissionForm::where(
                    'form_id',
                    $payment->form_id
                )->update([
                    'status' => 'S',
                    'admission_form_status' => 'Payment Successful',
                ]);

                return $this->paymentRedirect([
                    'success' => true,
                    'message' => 'Payment successful.',
                    'data' => [
                        'order_id' => $payment->OrderId,
                        'form_id' => $payment->form_id,
                        'status' => 'S',
                        'transaction_reference' => $reference,
                    ],
                ]);
            }

            // Payment failed.
            $payment->update([
                'status'      => 'F',
                'Status_code' => 'F',
                'Status_desc' => $status ?: 'Payment Failed',
            ]);

            // Update admission form status.
            OnlineAdmissionForm::where(
                'form_id',
                $payment->form_id
            )->update([
                'status' => 'F',
                'admission_form_status' => 'Payment Failed',
            ]);

            return $this->paymentRedirect([
                'success' => false,
                'message' => 'Payment failed.',
                'data' => [
                    'order_id' => $payment->OrderId,
                    'form_id' => $payment->form_id,
                    'status' => 'F',
                    'status_description' => $status,
                ],
            ]);

        } catch (\Exception $e) {

            return $this->paymentRedirect([
                'success' => false,
                'message' => 'Unable to process payment callback.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function paymentRedirect(array $payload, int $status = 302)
    {
        $url = config('payment.worldline.frontend_return_url');

        if (!$url) {
            return response()->json($payload, $status);
        }

        return redirect()->away($url . (str_contains($url, '?') ? '&' : '?') . http_build_query([
            'success' => $payload['success'] ? '1' : '0',
            'form_id' => $payload['data']['form_id'] ?? null,
            'order_id' => $payload['data']['order_id'] ?? null,
            'status' => $payload['data']['status'] ?? null,
            'message' => $payload['message'] ?? null,
        ]));
    }
}