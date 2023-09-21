<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Billing\PaymentGateway;
use Illuminate\Http\Request;

class PaymentController extends Controller
{


    protected $paymentService;

    public function __construct(PaymentGateway $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function paymentResponse(Request $request){


        if($request->notification_type == 'check'){
            return response()->json([
                'status' => 'ok'
            ]);
        }
        $payment = Payment::where('payment_token', $request->token)->first();
        if(!$payment){
            return response()->json([
                'status' => 'error',
                "message" => 'Платеж не найден'
            ]);
        }
        if($request->notification_type == 'pay'){
            if($request->status == 'successful'){
                  $customParameters = $request->custom_parameters;
                  if(!empty($customParameters)){
                      if($customParameters['payment_reason'] == 'add_card'){
                          $this->paymentService->savePaymentMethod($request->payment_method, $request->subscription['token'], $request->token, $request->status, $this->paymentService);
                      }
                  }
                $payment->setSuccessStatus();
            }
        }
        if($request->notification_type == 'error'){
            info('ERROR');
            info($request);
            $customParameters = $request->custom_parameters;
            if(!empty($customParameters)){
                if($customParameters['payment_reason'] == 'add_card'){
                    $this->paymentService->savePaymentMethod($request->payment_method, $request->subscription['token'], $request->token, $request->status, $this->paymentService);
                }
                $payment->setErrorStatus();
            }

            return response()->json([
                'status' => 'error',
                "message" => $request->status_description
            ]);
        }

    }


}
