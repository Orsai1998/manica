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
        info("===========REQUEST===========");
        info($request->all());

        if($request->notification_type == 'check'){
            info("===================CHECK==========================");
            info($request->all());
            return response()->json([
                'status' => 'ok'
            ]);
        }
        if($request->status == "wait_capture"){
            info("===================WAIT CAPTURE==========================");
            info($request->all());
            return response()->json([
                'status' => 'ok'
            ]);
        }
        if($request->notification_type == 'wait_capture'){
            info("===================WAIT==========================");
            info($request->all());
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
                          $this->paymentService->savePaymentMethod($customParameters['user_id'],
                              $request->payment_method, $request->subscription['token'], $request->token,
                              $request->status, $this->paymentService);
                      }
                  }
                $payment->setSuccessStatus();
            }
            return response()->json([
                'status' => 'ok'
            ]);
        }
        if($request->notification_type == 'error'){
            info('ERROR');
            info($request);
            $customParameters = $request->custom_parameters;
            if(!empty($customParameters)){
//                if($customParameters['payment_reason'] == 'add_card'){
//                    $this->paymentService->savePaymentMethod($customParameters['user_id'],
//                        $request->payment_method, $request->subscription['token'], $request->token,
//                        $request->status, $this->paymentService);
//                }
                $payment->setErrorStatus();
            }

            return response()->json([
                'status' => 'error',
                "message" => htmlentities($request->status_description)
            ]);
        }

    }

    public function success(){
        return view('payment.success');
    }

    public function fail(){
        return view('payment.fail');
    }

}
