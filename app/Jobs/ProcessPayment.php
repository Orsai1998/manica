<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\Billing\PaymentGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $payment;
    protected $paymentService;
    public function __construct(PaymentGateway $paymentService, Payment $payment)
    {
        $this->payment = $payment;
        $this->paymentService = $paymentService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->payment->status == 'PENDING'){
            $response = $this->paymentService->getPaymentInfo($this->payment->payment_token);

            if(!empty($response)){
                if($response['status'] == 'successful'){
                    $this->payment->setSuccessStatus();
                }else{
                    $this->release(now()->addMinutes(10));
                }
            }
        }
    }
}
