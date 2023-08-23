<?php

namespace App\Http\Controllers;

use App\Billing\PaymentGateway;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{


    protected $paymentService;

    public function __construct(PaymentGateway $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function paymentResponse(Request $request){

        $payment = Payment::where('payment_token', $request->token)->first();
        if(!$payment){
            return response()->json([
                'status' => 'error',
                "message" => 'Платеж не найден'
            ]);
        }
        if($request->notification_type == 'check'){
            return response()->json([
                'status' => 'ok'
            ]);
        }
        if($request->notification_type == 'pay'){
            if($request->status == 'successful'){
                  $customParametrs = $request->custom_parameters;
                  if(!empty($customParametrs)){
                      if($customParametrs['payment_reason'] == 'add_card'){
                          $payment->setSuccessStatus();
                          $this->paymentService->savePaymentMethod($request->payment_method, $request->subscription['token'], $request->token, $request->status, $this->paymentService);
                      }
                  }
            }
        }
        if($request->notification_type == 'error'){
            if(!empty($customParametrs)){
                if($customParametrs['payment_reason'] == 'add_card'){
                    $payment->setErrorStatus();
                    $this->paymentService->savePaymentMethod($request->payment_method, $request->subscription['token'], $request->token, $request->status, $this->paymentService);
                }
            }
            return response()->json([
                'status' => 'error',
                "message" => $request->status_description
            ]);
        }

    }


}
