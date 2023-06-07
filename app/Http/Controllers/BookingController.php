<?php

namespace App\Http\Controllers;

use App\Billing\PaymentGateway;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $validator = Validator::make($request->all(), [
            'apartment_id' => 'required | exists:apartments,id',
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
        DB::beginTransaction();

        try {
            $request->merge(['status' => 'PENDING']);
            $request->merge(['user_id' => $user->id]);

            $booking = Booking::create($request->all());
            $payment =  $this->paymentService->createPayment($request->total_sum, $booking->id);

            DB::commit();
            return response()->json(['success'=> true, 'payment' => json_encode($payment)]);

        }catch (\Exception $exception){
            DB::rollback();
            return response()->json([
                'success'=>false,
                'message'=>$exception->getMessage()
            ]);
        }

    }
}
