<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnlineAdmissionFee;
use App\Models\OnlineAdmissionForm;
use App\Models\NewAdmissionClass;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdmissionPaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        // 1. Validate form_id
        $validated = $request->validate([
            'form_id' => 'required|string|max:20',
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

            // No Worldline transaction reference yet.
            // It will be updated after payment succeeds.
            'Trnx_ref_no'  => 0,

            'rrn'          => null,

            'Status_code'  => 'A',
            'Status_desc'  => 'Payment Attempted',
            'synced_later' => 'N',

            'academic_yr'  => $admissionForm->academic_yr,
        ]);

        // 8. Return payment information
        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully.',
            'data' => [
                'payment_id' => $payment->adfees_payment_id,
                'form_id'    => $payment->form_id,
                'order_id'   => $payment->OrderId,
                'amount'     => $payment->amount,
                'currency'   => 'INR',
                'status'     => $payment->status,
                'academic_yr' => $payment->academic_yr,
                'class_id'   => $admissionForm->class_id,
            ],
        ], 201);
    }
}