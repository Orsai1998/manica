<?php

namespace App\Jobs;

use App\Billing\PaymentGateway;
use App\Models\Payment;
use App\Models\UserPaymentCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPaymentsCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $payment;
    protected $paymentCard;
    protected $paymentService;
    public $tries = 3;

    public function __construct(PaymentGateway $paymentService, Payment $payment, UserPaymentCard $paymentCard)
    {
        $this->payment = $payment;
        $this->paymentCard = $paymentCard;
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
                    $this->paymentCard->status = 'successful';
                    $this->paymentCard->save();

                    try {
                        $this->paymentService->refundPayment($this->payment->payment_token,0 ,0,10, "Отмена покупки");
                    }
                    catch (\Exception $exception){
                        $this->release(now()->addMinutes(3));
                    }
                }else{
                    $this->release(now()->addMinutes(3));
                }
            }
        }
    }
}
