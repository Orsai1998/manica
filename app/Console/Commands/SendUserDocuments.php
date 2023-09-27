<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\IntegrationOneCService;
use Illuminate\Console\Command;

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
        $users= User::whereHas('documents', function ($q){
            $q->whereNotNull('name');
            $q->whereNull('isSentTo1C');
        })->get();

        foreach ($users as $user){
            if(!empty($user->documents)){
               $this->integrationOneCService->sendUserDocuments($user);
               echo "Фото документа отправлено ". $user->one_c_guid. "\n";
            }else{
                echo 'Нет документов клиента '.$user->id;
            }
        }

        echo 'Успешно отправлены';
    }
}
