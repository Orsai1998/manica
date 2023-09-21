<?php

namespace App\Services\Billing;

use App\Models\UserPaymentCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use KassaCom\SDK\Exception\ServerResponse\ResponseException;
use KassaCom\SDK\Exception\TransportException;
use function config;

class PaymentGatewayWithSDK
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
        $this->makeAuth();
    }

    protected function makeAuth(){
        $guzzleClient = new \GuzzleHttp\Client();
        $transport = new \KassaCom\SDK\Transport\GuzzleApiTransport($guzzleClient);
        $this->client = new \KassaCom\SDK\Client($transport);
        $this->client->setAuth('arenaa2012@mail.ru', '18D33336-6D5A-4F00-804B-170FB11FE160');
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
            $createPaymentResponse =  $this->client->createPayment($requestArray);
            $token = "";
            $ip = "";
            Log::info("==============PAYMENT INFO ==================");
            Log::info($createPaymentResponse);
            if($createPaymentResponse){
                $token = $createPaymentResponse->getToken();
                $ip = $createPaymentResponse->getIp();
            }

            if(!empty($token) && !empty($ip)){

                return [
                    'token' => $token,
                    'ip' => $ip
                ];
            }
            throw new \Exception('Token or IP is empty');

        } catch (ResponseException|TransportException $e) {
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
         $processPayment = new \KassaCom\SDK\Model\Request\Payment\ProcessPaymentRequest();
         $processPayment->setIp($userIp);
         $processPayment->setToken($token);
         $processPayment->setPaymentMethodData($paymentMethod);


         try {

             $proceedPaymentResponse =  $this->client->processPayment($requestArray);

             if(!empty($proceedPaymentResponse)){
                 $token = $proceedPaymentResponse->getToken();
             }

             return $token;
         }catch (\Exception $exception){
             Log::channel('interpay-error')->error($exception);
             throw new \Exception($exception->getMessage(). " ".$exception->getCode());
         }
    }

    public function cancelPayment(String $token){
        $cancelPaymentRequest = new \KassaCom\SDK\Model\Request\Payment\CancelPaymentRequest($token);
        try {
           $this->client->cancelPayment($cancelPaymentRequest);
        } catch (\Exception $exception) {
            Log::channel('interpay-error')->error($exception);
        }

    }

    public function refundPayment(String $token, String $amount, String $description){

    }

    public function getPaymentInfo(String $token){

        try {
            $payment = $this->client->getPayment($token);
            Log::info($payment);
            if($payment->getStatus() == 'successful'){
                $this->savePaymentMethod($payment->getPaymentMethod(), $token);
            }
            return $payment;

        }catch (\Exception $exception){
            Log::channel('interpay-error')->error($exception);
            throw new \Exception($exception->getMessage(). " ".$exception->getCode());
        }
    }

    protected function savePaymentMethod(\KassaCom\SDK\Model\Response\Item\PaymentMethodItem $paymentMethod, $token){

        $user = Auth::user();
        if($user){

            try {
                $userPayment = UserPaymentCard::where('fingerprint', $paymentMethod->getCard()->getFingerprint())->first();

                if($userPayment){
                    $this->cancelPayment($token);
                    throw new \Exception('Такой метод оплаты уже существует');
                }
                $userPayment = new UserPaymentCard();
                $userPayment->user_id = $user->id;
                $userPayment->account = $paymentMethod->getAccount();
                $userPayment->fingerprint = $paymentMethod->getCard()->getFingerprint();
                $userPayment->bank = $paymentMethod->getCard()->getBank();
                $userPayment->brand = $paymentMethod->getCard()->getBrand();
                $userPayment->is_main = count($user->payment_cards) > 0 ? 0 : 1;
                $userPayment->save();

                $this->cancelPayment($token);
            }catch (\Exception $exception){
                Log::error($exception);
                throw new \Exception($exception->getMessage(). " ".$exception->getCode());
            }
        }

    }

}
