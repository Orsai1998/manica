<?php

namespace App\Console\Commands;

use App\Models\Payment;
use \App\Billing\PaymentGateway;
use App\Models\User;
use App\Services\IntegrationOneCService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendUserDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sent:documents';

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
    public function __construct(IntegrationOneCService $integrationOneCService)
    {
        parent::__construct();

        $this->integrationOneCService = $integrationOneCService;
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users= User::all();

        foreach ($users as $user){
            if(!empty($user->documents)){
               $this->integrationOneCService->sendUserDocuments($user);
            }else{
                echo 'Нет документов клиента '.$user->id;
            }
        }

        echo 'Успешно отправлены';
    }
}
