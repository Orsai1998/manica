<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserDebtsResource;
use App\Http\Resources\UserPaymentResource;
use App\Http\Resources\UserResource;
use App\Jobs\ProcessPaymentsCard;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserDebt;
use App\Models\UserDocument;
use App\Models\UserPaymentCard;
use App\Services\Billing\PaymentGateway;
use App\Services\IntegrationOneCService;
use App\Traits\UserExtension;
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
    use UserExtension;

    public function __construct(PaymentGateway $paymentService, IntegrationOneCService $integrationService)
    {
        $this->paymentService = $paymentService;
        $this->integrationService = $integrationService;
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

        if($request->hasFile('front_ID')){
            $frontId = $request->file('front_ID');
            $this->saveUserDocument(1,$frontId,$user);

        }
        if($request->hasFile('back_ID')){
            $backId = $request->file('back_ID');
            $this->saveUserDocument(0,$backId,$user);
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
        $amount = 10;

        try {
            $user = User::find($user->id);
            $paymentLocal = $this->createPayment($user);
            $payment =  $this->paymentService->createPayment($amount, $paymentLocal->guid,"","","add_card");
            $paymentLocal->setToken($payment['token']);

            if($payment){
                try {

                    return response()->json([
                        'success' => true,
                        'payment_url' => $payment['payment_url']
                    ]);
                }catch (\Exception $exception){
                    return response()->json([
                        'success' => false,
                        'message' => $exception->getMessage()
                    ]);
                }

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

    public function getUserDebt(Request $request){
        $user = Auth::user();
        $needToPay = $request->boolean('needToPay');
        if($user->getUserDebts){

            if($request->paymentType == "depozit"){
                $data = UserDebt::where('user_id', $user->id)
                    ->where('paymentType', $request->paymentType)
                    ->where('needToPay', "=" ,$needToPay)->get();
                return UserDebtsResource::collection($data);
            }
            if(!$request->has('paymentType')){
                if(!$needToPay){
                    $data = Payment::whereHas('bookings',function ($query){
                        $query->where('departure_date','<', now());
                    })->where('user_id', $user->id)->history()->orderBy('booking_id','desc')->get();
                    return UserPaymentResource::collection($data);
                }
                $data = UserDebt::where('user_id', $user->id)
                    ->where('paymentType', '=', 'accommodation')
                    ->where('needToPay', "=" ,$needToPay)->get();
                return UserDebtsResource::collection($data);
            }
        }

        return [];
    }

    public function payDebt(Request $request){

        $validator = Validator::make($request->all(), [
            'debt_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $userDebt = UserDebt::find($request->debt_id);
        if(!$userDebt){
            return response()->json([
                'success'=>false,
                'message'=>'Задолжность не найдена'
            ]);
        }
        $booking = Booking::find($userDebt->userBooking());
        if(!$userDebt->userBooking() && !$booking){
            return response()->json([
                'success'=>false,
                'message'=>'Не найдена бронь по задолжности'
            ]);
        }

        $user = User::find(Auth::user()->id);

        $userPaymentCard = $user->payment_cards->where('is_main','=','1')->first();

        if(!$userPaymentCard){
            return response()->json([
                'success'=>false,
                'message'=>'Добавьте методы оплаты'
            ]);
        }
        DB::beginTransaction();

        try {

            $paymentLocal = $this->paymentService->createLocalPayment($user, $userPaymentCard,
                $userDebt->userBooking(), abs($userDebt->balance), $userDebt->paymentType);

            if($paymentLocal){
                $payment =  $this->paymentService->createPayment(abs($userDebt->balance), $paymentLocal->guid,
                    "Оплата ". $userDebt->paymentType." №".$userDebt->userBooking()." в приложений MANICA.kz",
                    $userPaymentCard->subscription_token);

                $paymentLocal->setToken($payment['token']);
                $paymentInfo = $this->paymentService->getPaymentInfo($payment['token']);

                if($paymentInfo['status'] == 'successful'){
                    $this->changeStatusToPaid($paymentLocal->id);
                    $this->integrationService->createPayment($booking, $user,$userDebt->paymentType ,
                        abs($userDebt->balance), $paymentLocal->guid);
                }
                if($paymentInfo['status'] == 'error'){
                    $message = 'Unknown status payment error';
                    if(count($paymentInfo['error_details']) > 0){
                        $message = $paymentInfo['error_details']['description'];
                    }
                    throw new \Exception($message);
                }
                return response()->json(['success'=> true]);
            }
            return response()->json(['success'=> false, 'message' => 'Ошибка при созданий платежа']);

        }catch(\Exception $exception){
            DB::rollBack();
            return response()->json(['success'=> false, 'message' => $exception->getMessage()]);
        }
    }

    protected function changeStatusToPaid($payment_id){
        $payment = Payment::find($payment_id);
        $payment ->status = 'PAID';
        $payment->save();
    }

    public function deleteAvatar(){
        $user = Auth::user();

        $user = User::find($user->id);

        $user->avatar = null;
        $user->save();

        return response()->json([
            'success'=>true,
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
                    //$this->paymentService->refundPayment($token,0,0,10, "Отмена покупки");
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
