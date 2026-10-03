<?php

require_once __DIR__ . "/../config/razorpay.php";

class PaymentService
{
    private string $keyId;
    private string $keySecret;

    public function __construct()
    {
        $this->keyId = RAZORPAY_KEY_ID;
        $this->keySecret = RAZORPAY_KEY_SECRET;
    }


    /*
    |--------------------------------------------------------------------------
    | Create Razorpay Order
    |--------------------------------------------------------------------------
    */

    public function createOrder(
        string $receipt,
        float $amount,
        string $currency = 'INR'
    ): array {

        $url = "https://api.razorpay.com/v1/orders";

        $data = [
            "amount" => (int) round($amount * 100),
            "currency" => $currency,
            "receipt" => $receipt
        ];


        $ch = curl_init($url);

        curl_setopt_array($ch, [

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_POST => true,

            CURLOPT_POSTFIELDS => json_encode($data),

            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ],

            CURLOPT_USERPWD => $this->keyId . ":" . $this->keySecret,

            CURLOPT_TIMEOUT => 30

        ]);


        $response = curl_exec($ch);

        $httpCode = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        $curlError = curl_error($ch);

        curl_close($ch);


        if ($response === false) {

            throw new Exception(
                "Razorpay API Error: " . $curlError
            );
        }


        $result = json_decode(
            $response,
            true
        );


        if ($httpCode < 200 || $httpCode >= 300) {

            $message = $result['error']['description']
                ?? 'Unable to create Razorpay order.';

            throw new Exception($message);
        }


        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | Verify Razorpay Payment Signature
    |--------------------------------------------------------------------------
    */

    public function verifySignature(
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature
    ): bool {

        $generatedSignature = hash_hmac(
            'sha256',
            $razorpayOrderId . '|' . $razorpayPaymentId,
            $this->keySecret
        );


        return hash_equals(
            $generatedSignature,
            $razorpaySignature
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Get Razorpay Key ID
    |--------------------------------------------------------------------------
    */

    public function getKeyId(): string
    {
        return $this->keyId;
    }
}