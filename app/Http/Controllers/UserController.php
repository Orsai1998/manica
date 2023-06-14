<?php

namespace App\Http\Controllers;

use App\Billing\PaymentGateway;
use App\Http\Resources\UserResource;
use App\Models\UserPaymentCard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    protected $paymentService;

    public function __construct(PaymentGateway $paymentService)
    {
        $this->paymentService = $paymentService;
    }


    public function index() : JsonResource{

        $user = Auth::user();

        return new UserResource($user);
    }

    public function update(Request $request){

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'isFemale' => 'required',
            'birth_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $name = $request->input('name');
        $isFemale = $request->input('isFemale');
        $birth_date = $request->input('birth_date');

        if($request->hasFile('avatar')){
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpg,png,jpeg,gif',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success'=>false,
                    'message'=>$validator->errors()
                ]);
            }

            $avatar = $request->file('avatar');
            $avatarFileName = $avatar->getClientOriginalName();
            $avatarPath = $avatar->storeAs('avatar', $avatarFileName, 'public');

            if(!empty($avatarPath)){
                $user->avatar = $avatarPath;
            }

        }

        $user->name = $name;
        $user->isFemale = $isFemale;
        $user->birth_date = $birth_date;
        $user->save();

        return response()->json([
            'success'=>true,
        ]);
    }

    public function addUserPaymentCard(Request $request){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'card_number' => 'required',
            'card_year' => 'required',
            'card_month' => 'required',
            'card_security' => 'required',
            'cardholder' => 'required',
        ]);
        $amount = 10;
        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        try {
        $payment =  $this->paymentService->createPayment($amount, "1");

            $token = $payment['token'];
            $ip = $payment['ip'];
            $paymentMethod = [
                'card_number' => $request->card_number,
                'type' => 'card',
                'card_year' => $request->card_year,
                'card_month' => $request->card_month,
                'card_security' => $request->card_security,
                'cardholder' => $request->cardholder
            ];
            $payment =  $this->paymentService->proceedPayment($token, $ip, $paymentMethod);

            if($payment){
                 $paymentInfo = $this->paymentService->getPaymentInfo($token);
                  if($paymentInfo['status'] == 'successful' && $paymentInfo['subscription']['status'] == 'active'){
                      $this->savePaymentMethod($paymentInfo['payment_method'], $paymentInfo['subscription']['token'], $payment['token']);

                  }
                return response()->json([
                   'success' => true
                ]);
            }
            return response()->json([
                'success'=>false,
            ]);

        }catch (\Exception $exception){

            return response()->json([
                'success'=>false,
                'message'=> $exception->getMessage()
            ]);
        }
    }

   protected function savePaymentMethod(array $paymentMethod, String $subscription_token, String $token){

        $user = Auth::user();
        if($user){
            DB::beginTransaction();
            try {
                $userPayment = UserPaymentCard::where('subscription_token', $token)->first();

                if($userPayment){
                    $this->paymentService->refundPayment($token, 10, "Отмена покупки");
                    throw new \Exception('Такой метод оплаты уже существует');
                }
                $userPayment = new UserPaymentCard();
                $userPayment->user_id = $user->id;
                $userPayment->account = $paymentMethod['account'];
                $userPayment->subscription_token = $subscription_token;
                $userPayment->bank = $paymentMethod['card']['bank'];
                $userPayment->brand = $paymentMethod['card']['brand'];;
                $userPayment->is_main = count($user->payment_cards) > 0 ? 0 : 1;
                $userPayment->save();

                //Возврат суммы после привязки карты
                $this->paymentService->refundPayment($token, 10, "Отмена покупки");

              DB::commit();
            }catch (\Exception $exception){
                Log::error($exception);
                DB::rollBack();
                throw new \Exception($exception->getMessage(). " ".$exception->getCode());
            }
        }

    }

}
