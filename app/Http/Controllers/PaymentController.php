<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{


    public function paymentResponse(Request $request){

        Log::info(print_r($request));
    }
}
