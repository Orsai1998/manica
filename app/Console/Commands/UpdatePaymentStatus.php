<?php

namespace App\Console\Commands;

use App\Models\Payment;
use \App\Billing\PaymentGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdatePaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PaymentGateway $paymentService)
    {
        parent::__construct();

        $this->paymentService = $paymentService;
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $payments = Payment::where('status','=','PROCESS')->get();

        foreach ($payments as $payment){
            $response = $this->paymentService->getPaymentInfo($payment->payment_token);

            if(!empty($response)){
                if($response['status'] == 'successful'){
                        $payment->setSuccessStatus();
                }
            }
        }
    }
}
