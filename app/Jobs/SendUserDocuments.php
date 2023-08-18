<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\IntegrationOneCService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendUserDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $user;
    protected $integrationOneCService;

    public function __construct(User $user, IntegrationOneCService $integrationOneCService)
    {
        $this->user = $user;
        $this->integrationOneCService = $integrationOneCService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = $this->user;
        if(!empty($user->documents)){
            $this->integrationOneCService->sendUserDocuments($user);
        }else{
            Log::info('Нет документов клиента '.$user->id);
        }
    }
}
