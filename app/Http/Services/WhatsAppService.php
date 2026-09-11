<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DB;

class WhatsAppService
{
    protected $userId;
    protected $password;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = 'https://mediaapi.smsgupshup.com/GatewayAPI/rest';
        $settings = DB::table('school_settings')
            ->where('is_active', 'Y')
            ->first();

        $this->userId = $settings->user_id ?? null;
        $this->password = $settings->password ?? null;
    }

    private function canSendWhatsapp()
    {
        $settings = DB::table('school_settings')
            ->where('is_active', 'Y')
            ->select('whatsapp_threshold', 'threshold_count')
            ->first();

        if (!$settings || $settings->whatsapp_threshold != 'Y') {
            return true;
        }

        $sentThisMonth = DB::table('redington_webhook_details')
            ->where('sms_sent', 'Y')
            ->where('status', '!=', 'failed')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return $sentThisMonth < $settings->threshold_count;
    }

    public function sendTextMessage($phoneNumber, $templateName = null, $parameters = [])
    {
        try {
            if (!$this->canSendWhatsapp()) {
                Log::channel('whatsapp')->warning('WhatsApp Monthly Threshold Reached', [
                    'phone' => $phoneNumber
                ]);

                return [
                    'success' => false,
                    'message' => 'Monthly WhatsApp limit reached.'
                ];
            }
            $message = implode("\n", $parameters);

          $response = Http::withoutVerifying()->get($this->apiUrl, [
                'userid' => $this->userId,
                'password' => $this->password,
                'send_to' => $phoneNumber,
                'v' => '1.1',
                'format' => 'json',
                'msg_type' => 'TEXT',
                'method' => 'SENDMESSAGE',
                'msg' => $message,
            ]);

            Log::channel('whatsapp')->info('Gupshup Response', [
                'phone' => $phoneNumber,
                'message' => $message,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('WhatsApp Error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}