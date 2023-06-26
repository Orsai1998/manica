<?php

namespace App\Models;

use http\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class VerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'code'
    ];

    public function sendSMS($receiverNumber)
    {
        $message = "Login OTP is ".$this->code;

        try {

            $api_key= config('services.mobizone.apiKey');
            $api= config('services.mobizone.api');
            $api = new \Mobizon\MobizonApi($api_key, $api);

            $api->call('message',
                'sendSMSMessage',
                array(
                    'recipient' => $receiverNumber,
                    'text' => $message,
                ));

            if ($api->hasData()) {
                foreach ($api->getData() as $messageInfo) {
                    info('Message # ' . $messageInfo->id . " status:\t" . $messageInfo->status . PHP_EOL);
                }
            }

        } catch (\Exception $e) {
            info("Error: ". $e->getMessage());
        }
    }
}
