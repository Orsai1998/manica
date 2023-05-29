<?php

namespace App\Models;

use http\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        $message = "Login OTP is ".$this->otp;

        try {

            $account_sid = getenv("MOBIZONE_ACC");
            $auth_token = getenv("TWILIO_TOKEN");
            $twilio_number = getenv("TWILIO_FROM");

            $client = new Client($account_sid, $auth_token);
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message]);

            info('SMS Sent Successfully.');

        } catch (\Exception $e) {
            info("Error: ". $e->getMessage());
        }
    }
}
