<?php

namespace App\Http\Controllers;

use App\Billing\PaymentGateway;
use App\Http\Resources\UserResource;
use App\Jobs\ProcessPaymentsCard;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\UserPaymentCard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

            $user = User::find($user->id);
            $paymentLocal = $this->createPayment($user);
            $payment =  $this->paymentService->createPayment($amount, $paymentLocal->guid);
            $paymentLocal->setToken($payment['token']);
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
                 Log::info($paymentInfo);
                $this->savePaymentMethod($paymentInfo['payment_method'], $paymentInfo['subscription']['token'], $payment['token'], $paymentInfo['status'], $this->paymentService);

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
    protected function createPayment(User $user){
        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->booking_id = 0;
        $payment->user_card_id = 0;
        $payment->total_sum = '10';
        $payment->payment_token = '';
        $payment->paymentType = 'ADD_CARD';
        $payment->guid = (string) Str::uuid();
        $payment->save();

        return $payment;
    }

    public function deleteDocument(Request $request){
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'doc_id' => 'required',
        ]);

        $userDocument = UserDocument::where('user_id', $user->id)->where('id', $request->doc_id)->first();

        if($userDocument){
            $userDocument->delete();

            return response()->json([
                'success' => true
            ]);
        }
        return response()->json([
            'success'=>false,
            'message'=>'Doc not found'
        ]);
    }

    public function setDefaultCard(Request $request){
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'card_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $user_card = UserPaymentCard::where('id', $request->card_id)->where('user_id', $user->id)->first();

        if($user_card){
            $user_card_main = UserPaymentCard::where('is_main','=' , 1)->where('user_id', $user->id)->first();

            if($user_card_main){
                $user_card_main->is_main = 0;
                $user_card_main->save();
            }

            $user_card->is_main = 1;

            $user_card->save();
            return response()->json([
                'success'=>true,
            ]);
        }
        return response()->json([
            'success'=>false,
            'message'=>'Карта не найдена'
        ]);
    }

    public function deletePaymentCard(Request $request){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'card_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }
        $card = UserPaymentCard::where('id',$request->card_id)->where('user_id', $user->id)->first();

        if($card){
            $card->delete();
        }
        return response()->json([
            'success'=>true,
        ]);

    }

   protected function savePaymentMethod(array $paymentMethod, String $subscription_token, String $token, String $status, PaymentGateway $paymentService){

        $user = Auth::user();
        if($user){
            DB::beginTransaction();
            try {
                $userPayment = UserPaymentCard::where('fingerprint', $paymentMethod['card']['fingerprint'])
                    ->where('user_id', $user->id)->first();

                if($userPayment){
                    $this->paymentService->refundPayment($token,0,0,10, "Отмена покупки");
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
//                if($status == 'success'){
//                    $this->paymentService->refundPayment($token,0 ,0,10, "Отмена покупки");
//                }


              DB::commit();
            }catch (\Exception $exception){
                Log::error($exception);
                DB::rollBack();
                throw new \Exception($exception->getMessage(). " ".$exception->getCode());
            }
        }

    }

}
