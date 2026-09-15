<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class WorldlineService
{
    /**
     * Create Worldline payment request.
     */
    public function createPayment(array $paymentData)
    {
        $mid = config('payment.worldline.mid');
        $key = config('payment.worldline.key');
        $iv  = config('payment.worldline.iv');

        if (!$mid || !$key || !$iv) {
            throw new Exception('Worldline configuration is missing.');
        }

        $data = [
            'merchant' => [
                'identifier' => $mid,
                'responseEndpointURL' => config(
                    'payment.worldline.success_url'
                ),
            ],

            'cart' => [
                'item' => [
                    [
                        'amount' => $paymentData['amount'],
                        'identifier' => 'FIRST',
                        'description' => 'SACS',
                    ],
                ],
            ],

            'payment' => [
                'method' => [
                    'token' => '470',
                ],
                'instrument' => [
                    'token' => '',
                ],
            ],

            'transaction' => [
                'deviceIdentifier' => 'web',
                'amount' => $paymentData['amount'],
                'type' => 'SALE',
                'description' => '',
                'currency' => 'INR',
                'isRegistration' => 'N',
                'identifier' => $paymentData['order_id'],
                'dateTime' => now()->format('Y-m-d'),
                'requestType' => 'T',
            ],

            'consumer' => [
                'mobileNumber' => $paymentData['phone'],
                'emailID' => $paymentData['email'],
                'accountNo' => '',
            ],
        ];

        $jsonData = json_encode($data);

        if ($jsonData === false) {
            throw new Exception('Unable to create Worldline request JSON.');
        }

        // Encrypt request using AES-128-CBC.
        $encryptedData = $this->encryptJSON(
            $jsonData,
            $key,
            $iv
        );

        // Worldline/TPSL endpoint.
        $endpoint = 'https://www.tpsl-india.in/PaymentGateway/merchant2.pg/'
            . $mid;

        // Send encrypted request to Worldline.
        $response = Http::timeout(30)
    ->withBody($encryptedData, 'text/plain')
    ->post($endpoint);

        if (!$response->successful()) {
            throw new Exception(
                'Worldline payment gateway returned HTTP status '
                . $response->status()
            );
        }

        $encryptedResponse = trim($response->body());

        if ($encryptedResponse === '') {
            throw new Exception(
                'No response received from Worldline payment gateway.'
            );
        }

        // Decrypt Worldline response.
        $decryptedData = $this->decryptAesHex(
            $encryptedResponse,
            $key,
            $iv
        );

        if ($decryptedData === false || $decryptedData === '') {
            throw new Exception(
                'Unable to decrypt Worldline response.'
            );
        }

        $decodedResponse = json_decode(
            $decryptedData,
            true
        );

        if (!is_array($decodedResponse)) {
            throw new Exception(
                'Invalid JSON response received from Worldline.'
            );
        }

        /*
         * Worldline returns the bank authentication URL here.
         */
        $bankAcsUrl = $decodedResponse['paymentMethod']['aCS']['bankAcsUrl']
            ?? null;

        return [
            'response' => $decodedResponse,
            'bank_acs_url' => $bankAcsUrl,
        ];
    }

    /**
     * Encrypt data using AES-128-CBC and return hexadecimal string.
     *
     * Matches the old CodeIgniter encryptJSON() implementation.
     */
    private function encryptJSON(
        string $data,
        string $key,
        string $iv
    ): string {
        $encryptedData = openssl_encrypt(
            $data,
            'AES-128-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encryptedData === false) {
            throw new Exception(
                'Worldline request encryption failed.'
            );
        }

        return bin2hex($encryptedData);
    }

    /**
     * Decrypt hexadecimal AES-128-CBC response.
     *
     * Matches the old CodeIgniter decryptAesHex() implementation.
     */
    private function decryptAesHex(
        string $response,
        string $key,
        string $iv
    ): string {
        $encryptedData = hex2bin($response);

        if ($encryptedData === false) {
            throw new Exception(
                'Invalid hexadecimal response from Worldline.'
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
            throw new Exception(
                'Worldline response decryption failed.'
            );
        }

        return $decryptedData;
    }
}