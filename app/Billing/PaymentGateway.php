<?php

namespace App\Billing;

use App\Models\UserPaymentCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use KassaCom\SDK\Exception\ServerResponse\ResponseException;
use KassaCom\SDK\Exception\TransportException;

class PaymentGateway
{
    protected $email;
    protected $apiKey;
    protected $createPaymentUrl;
    protected $processPaymentUrl;
    protected $client;


    public function __construct()
    {
        $this->email = config('services.interpay.email');
        $this->apiKey = config('services.interpay.apiKey');
        $this->createPaymentUrl = config('services.interpay.create_payment_url');
        $this->processPaymentUrl = config('services.interpay.process_payment_url');

    }

    protected function makeRequest(string $uri, array $params = [])
    {
        $token = $this->email.':'.$this->apiKey;

        $response = Http::withToken($token);
        $response = $response->post($uri, $params);

        return $this->parseResponse($response);
    }

    protected function parseResponse(\Illuminate\Http\Client\Response $response)
    {

        if ($response->successful()) {
            $response =  $response->json();

            if(!$response || !is_array($response)) {
                Log::channel('interpay-error')->error($response);
                throw new \Exception('Unknown status payment error');
            }

            return $response;
        }

        if($response->failed()) {
            Log::error($response);
            throw new \Exception('Api service error with status: '.$response->status(), $response->status());
        }
    }

    public function createPayment($amount, $orderId){

        $requestArray = [

            'order' => [
                "currency" => "KZT",
                "amount" => $amount,
                "description" => "Оплата в приложений MANICA.kz"
            ],

            'settings' => [
                "project_id" => "4027",
                "payment_method" => "card",
                "success_url" => "http://site.com/?success",
                "fail_url" => "http://site.com/?fail",
                'wallet_id' => 8413,
                'create_subscription' => true
            ],
            'custom_parameters' => [
                "order_id" => $orderId
            ],

        ];
        try {
            $createPaymentResponse =  $this->makeRequest('https://api.kassa.com/v1/payment/create', $requestArray);

            if($createPaymentResponse){

                return [
                    'ip' => $createPaymentResponse['ip'],
                    'token' => $createPaymentResponse['token']
                ];
            }
        } catch (\Exception $e) {
            Log::channel('interpay-error')->error($e);
            throw new \Exception($e->getMessage());
        }

    }


     function proceedPayment(String $token, String $userIp, $paymentMethod){
         $requestArray = [
            'token' => $token,
            'ip' => $userIp,
            'payment_method_data' => $paymentMethod
         ];


         try {
             $processPayment = $this->makeRequest('https://api.kassa.com/v1/payment/process', $requestArray);

             return [
                 'token' => $processPayment['token']
             ];

         }catch (\Exception $exception){
             Log::channel('interpay-error')->error($exception);
             throw new \Exception($exception->getMessage(). " ".$exception->getCode());
         }
    }


    public function refundPayment(String $token, String $amount, String $description){
        $requestArray = [
            'token' => $token,
            'refund' => [
                'amount' => $amount,
                'currency' => 'KZT',
                'reason' => $description
            ]
        ];

        try {
            return $this->makeRequest('https://api.kassa.com/v1/refund/create', $requestArray);

        }catch (\Exception $exception){
            Log::channel('interpay-error')->error($exception);
            throw new \Exception($exception->getMessage(). " ".$exception->getCode());
        }
    }

    public function getPaymentInfo(String $token){

        $requestArray = [
            'token' => $token,
        ];

        try {
            return $this->makeRequest('https://api.kassa.com/v1/payment/get', $requestArray);
        }catch (\Exception $exception){
            Log::channel('interpay-error')->error($exception);
            throw new \Exception($exception->getMessage(). " ".$exception->getCode());
        }
    }

}
