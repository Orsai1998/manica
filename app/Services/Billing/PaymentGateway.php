<?php

namespace App\Services\Billing;

use App\Jobs\ProcessPaymentsCard;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserPaymentCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use function config;

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
            $responseMes = $response->json();

            if($responseMes['message']){
                throw new \Exception($responseMes['error'].' '.$responseMes['message']);
            }

            throw new \Exception('Api service error with status: '.$response->status(), $response->status());
        }
    }
    public function createLocalPayment(User $user, UserPaymentCard $userPaymentCard, $booking_id, $total_sum, $type){
        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->booking_id = $booking_id;
        $payment->user_card_id = $userPaymentCard->id;
        $payment->total_sum = $total_sum;
        $payment->payment_token = '';
        $payment->paymentType = $type;
        $payment->guid = (string) Str::uuid();
        $payment->save();
        return $payment;
    }

    public function createPayment($amount, $orderId, $description = "", $subscription_token = "", $payment_reason = "accommodation"){
        $user = Auth::user();

        $requestArray = [
            "partner_payment_id" => $orderId,
            'order' => [
                "currency" => "KZT",
                "amount" => $amount,
                "description" => $description ?? "Оплата в приложений MANICA.kz"
            ],

            'settings' => [
                "project_id" => "4027",
                "payment_method" => "card",
                'wallet_id' => 8413,
                "success_url" =>URL::to('/payment/success'),
                "fail_url"=> URL::to('/payment/fail'),
                'create_subscription' => true,
                'capture' => false,
                'is_test' => false,
                'notification_url' => URL::to('/api/payment_response'),
            ],
            'custom_parameters' => [
                "order_id" => $orderId,
                "user_id" => $user->id,
                "payment_reason" => $payment_reason
            ],
        ];
        info("===========CREATE PAYMENT=================");
        info($requestArray);
        if(!empty($subscription_token)){
            $requestArray['settings']['subscription_token'] = $subscription_token;
        }

        try {
            $createPaymentResponse =  $this->makeRequest('https://api.kassa.com/v1/payment/create', $requestArray);

            if(empty($createPaymentResponse['token'])){
                throw new \Exception(json_encode($createPaymentResponse));

            }
            DB::commit();
            return [
                'ip' => $createPaymentResponse['ip'],
                'token' => $createPaymentResponse['token'],
                'payment_url' => $createPaymentResponse['payment_url'] ?? ""
            ];

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


    public function refundPayment($user_id,String $token, $external_id,$user_card_id, String $amount, String $description){
        $user = User::find($user_id);
        $requestArray = [
            'token' => $token,
            'refund' => [
                'amount' => $amount,
                'currency' => 'KZT',
                'reason' => $description
            ]
        ];

        try {
            Log::info("============REFUND============");
            info($requestArray);
            $this->createPaymentLogForRefund($user,$external_id, $user_card_id, $amount,$token);
            $response = $this->makeRequest('https://api.kassa.com/v1/refund/create', $requestArray);
            Log::info($response);
            return $response;
        }catch (\Exception $exception){
            Log::channel('interpay-error')->error($exception);
            throw new \Exception($exception->getMessage(). " ".$exception->getCode());
        }
    }

    protected function createPaymentLogForRefund(User $user, $booking_id = 0, $user_card_id = 0, $total_sum, $token = ''){
        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->booking_id = $booking_id;
        $payment->user_card_id = $user_card_id;
        $payment->total_sum = -$total_sum;
        $payment->payment_token = $token;
        $payment->paymentType = 'refund';
        $payment->guid = (string) Str::uuid();
        $payment->save();

        return $payment;
    }

    public function cancelPayment(String $token){
        if(empty($token)){
            return '';
        }

        $requestArray = [
            'token' => $token,
        ];

        try {
            return $this->makeRequest('https://api.kassa.com/v1/payment/cancel', $requestArray);

        }catch (\Exception $exception){
            Log::channel('interpay-error')->error($exception);
            throw new \Exception($exception->getMessage(). " ".$exception->getCode());
        }
    }

    public function savePaymentMethod($user_id,array $paymentMethod, String $subscription_token, String $token, String $status, PaymentGateway $paymentService){

        $user = User::find($user_id);
        if($user){
            DB::beginTransaction();
            try {
                $userPayment = UserPaymentCard::where('fingerprint', $paymentMethod['card']['fingerprint'])
                    ->where('user_id', $user->id)->first();

                if($userPayment){
                    $paymentService->refundPayment($user_id,$token,0 ,0,10, "Отмена покупки");
                    throw new \Exception('Такой метод оплаты уже существует');
                }
                $userPayment = new UserPaymentCard();
                $userPayment->user_id = $user->id;
                $userPayment->account = $paymentMethod['account'];
                $userPayment->subscription_token = $subscription_token;
                $userPayment->status = $status;
                $userPayment->fingerprint = $paymentMethod['card']['fingerprint'];
                $userPayment->bank = $paymentMethod['card']['bank'] ?? "";
                $userPayment->brand = $paymentMethod['card']['brand'];;
                $userPayment->is_main = count($user->payment_cards) > 0 ? 0 : 1;
                $userPayment->save();

                $payment = Payment::where('payment_token', $token)->first();
                $payment->user_card_id = $userPayment->id;
                $payment->save();
                ProcessPaymentsCard::dispatch($paymentService, $payment,$userPayment);
//                //Возврат суммы после привязки карты
              if($status == 'successful'){
                  $paymentService->refundPayment($user_id,$token,0 ,0,10, "Отмена покупки");
               }

                DB::commit();

            }catch (\Exception $exception){
                DB::rollBack();
                Log::error($exception);
                throw new \Exception($exception->getMessage(). " ".$exception->getCode());
            }
        }

    }

    public function getPaymentInfo(String $token){

        $requestArray = [
            'token' => $token,
        ];

        try {
            $response = $this->makeRequest('https://api.kassa.com/v1/payment/get', $requestArray);
            Log::info($response);
            return $response;

        }catch (\Exception $exception){
            Log::channel('interpay-error')->error($exception);
            //throw new \Exception($exception->getMessage(). " ".$exception->getCode());
        }
    }

}
