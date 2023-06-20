<?php

namespace App\Http\Controllers;

use App\Billing\PaymentGateway;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\CompanyInfo;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{

    protected $paymentService;

    public function __construct(PaymentGateway $paymentService)
    {
        $this->paymentService = $paymentService;
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        if($request->is_business_trip_reservation){
            $company =  CompanyInfo::where('booking_id', $request->booking_id)->where('user_id', $user->id)->first();

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

            $booking->update($request->all());
            $paymentService =  $this->paymentService->createPayment($request->total_sum, $booking->id,
                "Оплата брони №".$booking->id." в приложений MANICA.kz",
                $userPaymentCard->subscription_token);

            $payment = new Payment();
            $payment->user_id = $user->id;
            $payment->booking_id = $booking->id;
            $payment->user_card_id = $userPaymentCard->id;
            $payment->total_sum = $request->total_sum;
            $payment->payment_token = $paymentService['token'];
            $payment->save();

            $paymentInfo = $this->paymentService->getPaymentInfo($paymentService['token']);

            if($paymentInfo['status'] == 'successful'){
                $this->changeStatusToPaid($booking->id,$payment->id);
            }


            DB::commit();
            return response()->json(['success'=> true]);

        }catch (\Exception $exception){
            DB::rollback();
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
}
