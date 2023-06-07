<?php

namespace App\Billing;

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


    public function createPayment($amount, $orderId){
        $guzzleClient = new \GuzzleHttp\Client();
        $transport = new \KassaCom\SDK\Transport\GuzzleApiTransport($guzzleClient);
        $client = new \KassaCom\SDK\Client($transport);
        $client->setAuth('arenaa2012@mail.ru', '18D33336-6D5A-4F00-804B-170FB11FE160');

        $requestArray = [

            'order' => [
                "currency" => "EUR",
                "amount" => $amount,
                "description" => "Оплата в приложений MANICA.kz"
            ],

            'settings' => [
                "project_id" => "4027",
                "payment_method" => "card",
                "success_url" => "http://site.com/?success",
                "fail_url" => "http://site.com/?fail",
                'wallet_id' => 8413,
            ],
            'custom_parameters' => [
                "order_id" => $orderId
            ],

        ];
        try {
            $createPaymentResponse =  $client->createPayment($requestArray);
            Log::channel('interpay-error')->error(print_r($createPaymentResponse, true));

        } catch (ResponseException|TransportException $e) {
            Log::channel('interpay-error')->error($e->getMessage());

            throw new \Exception($e->getMessage());
        }


    }




}
