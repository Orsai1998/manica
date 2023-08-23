<?php

namespace App\Http\Controllers;

use App\Billing\PaymentGateway;
use App\Http\Resources\BookingDetailResource;
use App\Http\Resources\UserBookingsResource;
use App\Jobs\ProcessPayment;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\CompanyInfo;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserPaymentCard;
use App\Services\IntegrationOneCService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BookingController extends Controller
{

    protected $paymentService;
    protected $integrationService;
    protected $deposit_sum = 20000;
    public function __construct(PaymentGateway $paymentService, IntegrationOneCService $integrationService)
    {
        $this->paymentService = $paymentService;
        $this->integrationService = $integrationService;
    }


    public function create(Request $request){
        $user = Auth::user();
        $apartment = Apartment::find($request->apartment_id);

        $validator = Validator::make($request->all(), [
            'apartment_id' => 'required | exists:apartments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        if(!$apartment->is_available){
            return response()->json([
                'success'=>false,
                'message'=>'Апартаменты не доступны'
            ]);
        }

        $request->merge(['user_id' => $user->id]);
        $booking = Booking::create($request->all());

        return response()->json([
            'success'=> true,
            'booking_id'=> $booking->id
        ]);
    }

    public function pay(Request $request){

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'apartment_id' => 'required | exists:apartments,id',
            'id' => 'required | exists:bookings,id',
            'is_business_trip_reservation' => 'required',
            'number_of_adult' => 'required',
            'number_of_children' => 'required',
            'entry_date' => 'required',
            'departure_date' => 'required',
            'total_sum' => 'required',
            'deposit' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        if($request->deposit == 0){
            $request->deposit = $this->deposit_sum;
        }

        if($request->is_business_trip_reservation){
            $company =  CompanyInfo::where('user_id', $user->id)->first();

            if(!$company){
                return response()->json([
                    'success'=>false,
                    'message'=>'Заполните данные о компаний'
                ]);
            }
        }

        $userPaymentCard = $user->payment_cards->where('is_main','=','1')->first();

        if(!$userPaymentCard){
            return response()->json([
                'success'=>false,
                'message'=>'Добавьте методы оплаты'
            ]);
        }
        DB::beginTransaction();

        try {
            $request->merge(['user_id' => $user->id]);
            $booking = Booking::find($request->id);
            $payment = Payment::where('booking_id', $booking->id)->where('status','=' ,'PROCESS')->first();
            if($payment){
                return response()->json([
                    'success'=>true,
                    'message'=>'Платеж создан и находится в обработке'
                ]);
            }
            $user = User::find($user->id);

            $booking->update($request->all());

            $paymentDepozit = $this->createPayment($user, $userPaymentCard, $booking->id, $request->deposit, "depozit");
            $paymentAccomidation = $this->createPayment($user, $userPaymentCard, $booking->id, $request->total_sum, "accommodation");


            try {

                /*Отправка платежа за проживание в Kassa.com*/
                $paymentService =  $this->paymentService->createPayment($request->total_sum, $paymentAccomidation->guid,
                    "Оплата брони №".$booking->id." в приложений MANICA.kz",
                    $userPaymentCard->subscription_token);


                $paymentAccomidation->setToken($paymentService['token']);
                $paymentInfo = $this->paymentService->getPaymentInfo($paymentService['token']);

                if($paymentInfo['status'] == 'successful'){
                    $this->changeStatusToPaid($booking->id,$paymentAccomidation->id);
                }else{
                    Log::info('Статус оплаты брони №'. $booking->id. ' '. $paymentInfo['status']);
                    $this->changeStatusToUnknown($booking->id, $paymentAccomidation->id, $paymentInfo['status']);
                    ProcessPayment::dispatch($this->paymentService, $paymentAccomidation);
                }

                $paymentService =  $this->paymentService->createPayment($request->deposit, $paymentDepozit->guid,
                    "Оплата депозита брони №".$booking->id." в приложений MANICA.kz",
                    $userPaymentCard->subscription_token);


                $paymentDepozit->setToken($paymentService['token']);
                $paymentInfo = $this->paymentService->getPaymentInfo($paymentService['token']);
            }catch (\Exception $exception){
                return response()->json([
                    'success'=>false,
                    'message'=>$exception->getMessage()
                ], 500);
            }

            if($paymentInfo['status'] == 'successful'){
                $this->changeStatusToPaid($booking->id,$paymentDepozit->id);
            }else{
                Log::info('Статус оплаты депозита брони №'. $booking->id. ' '. $paymentInfo['status']);
                $this->changeStatusToUnknown($booking->id, $paymentDepozit->id, $paymentInfo['status']);
                ProcessPayment::dispatch($this->paymentService, $paymentDepozit);
            }

            /*Отправка в 1с*/
            $this->integrationService->createBooking($booking, $user);
            $this->integrationService->createPayment($booking, $user,"depozit" ,$request->deposit, $paymentDepozit->guid);
            $this->integrationService->createPayment($booking, $user,"accommodation" ,$request->total_sum, $paymentAccomidation->guid);

            DB::commit();
            return response()->json(['success'=> true]);

        }catch (\Exception $exception){
            DB::rollback();
            Log::error($exception);
            return response()->json([
                'success'=>false,
                'message'=>$exception->getMessage()
            ], 400);
        }

    }

    public function cancelBooking(Request $request){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'booking_id' => 'required | exists:bookings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        DB::beginTransaction();
        try {
            $booking = Booking::where('id', $request->booking_id)->where('user_id', $user->id)->first();
           if(!$booking){
               return response()->json([
                   'success'=>false,
                   'message'=>'Ваша бронь не найдена',
               ]);
           }
            $payment = Payment::where('booking_id', $booking->id)->first();
            if($payment){
                if(!empty($payment->payment_token)){
                    $this->paymentService->refundPayment($payment->payment_token,$booking->id, $booking->getPaymentMethodId(),$payment->total_sum, 'Отмена брони №'. $booking->id);
                }
            }

            $booking->status = 'CANCELED';
            $payment->status = 'CANCELED';
            $payment->save();
            $booking->save();
            DB::commit();
            $this->integrationService->cancelBooking($booking);

            return response()->json([
                'success'=>true,
            ]);
        }catch (\Exception $exception){
            DB::rollback();
            Log::error($exception);
            return response()->json([
                'success'=>false,
                'message'=>$exception->getMessage(),
            ]);
        }

    }

    protected function createPayment(User $user, UserPaymentCard $userPaymentCard, $booking_id, $total_sum, $type){
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


    public function getUserBookings(Request $request) : JsonResource{
        $active = $request->active;

        $user = Auth::user();
        $now = Carbon::now()->format('Y-m-d');
        if($active == 1){
            $booking = Booking::where('user_id', $user->id)
                ->whereDate('departure_date','>=',$now)->where('status','!=', 'CANCELED')->get();
        } else{
            $booking = Booking::where('user_id', $user->id)
                ->whereDate('departure_date','<',$now)->where('status','!=', 'CANCELED')->get();
        }

        return UserBookingsResource::collection($booking);
    }


    public function getBookingDetail(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required | exists:bookings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        try {
            $booking = Booking::find($request->booking_id);
            print_r($booking->payments);
            return new BookingDetailResource($booking);

        }catch (\Exception $exception){

            return response()->json([
                'success'=>false,
                'message'=>$exception->getMessage()
            ]);
        }

    }

    public function renewalBooking(Request $request){

        $validator = Validator::make($request->all(), [
            'booking_id' => 'required | exists:bookings,id',
            'new_departure_datetime' => 'required|after:'.Carbon::now()->format('Y-m-d H:i:s'),
            'total_sum' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $user = Auth::user();
        $booking = Booking::find($request->booking_id);
        $payment = Payment::where('booking_id', $booking->id)->where('status','=' ,'PROCESS')->first();

        if($payment){
            return response()->json([
                'success'=>true,
                'message'=>'Платеж создан и находится в обработке'
            ]);
        }
        DB::beginTransaction();

        try {
            $user = User::find($user->id);
            $userPaymentCard = $user->payment_cards->where('is_main','=','1')->first();
            //Создаем локальный платеж
            $payment = $this->createPayment($user, $userPaymentCard, $booking->id, $request->total_sum,"accommodation");
            //Создаем платеж на стороне kassa.com
            $paymentService =  $this->paymentService->createPayment($request->total_sum, $payment->id,
                "Оплата продлений брони №".$booking->id." в приложений MANICA.kz",
                $userPaymentCard->subscription_token);
            $payment->setToken($paymentService['token']);

            $paymentInfo = $this->paymentService->getPaymentInfo($paymentService['token']);
            $booking->departure_date = $request->new_departure_datetime;
            $booking->save();
            $message = "";
            if($paymentInfo['status'] == 'successful'){
                $this->changeStatusToPaid($booking->id, $payment->id);
                $this->integrationService->changeBooking($booking, $user);
            }else{
                Log::info('Статус оплаты брони №'. $booking->id. ' '. $paymentInfo['status']);
                $message = 'Статус оплаты брони №'. $booking->id. ' '. $paymentInfo['status'];
                $this->changeStatusToUnknown($booking->id, $payment->id, $paymentInfo['status']);
            }
            $this->integrationService->changeBooking($booking, $user);

            DB::commit();
            return response()->json([
                'success'=>true,
                'message'=>$message,
            ]);

        }catch (\Exception $exception){
            DB::rollback();
            Log::error($exception);
            return response()->json([
                'success'=>false,
                'message'=>$exception->getMessage()
            ]);
        }
    }


    protected function changeStatusToPaid($booking_id, $payment_id){

        $booking = Booking::find($booking_id);
        $payment = Payment::find($payment_id);

        $booking->status = 'PAID';
        $payment ->status = 'PAID';

        $booking->save();
        $payment->save();
    }
    protected function changeStatusToUnknown($booking_id, $payment_id, $status){

        $booking = Booking::find($booking_id);
        $payment = Payment::find($payment_id);

        $booking->status = $status;
        $payment ->status = $status;

        $booking->save();
        $payment->save();
    }
}
