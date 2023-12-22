<?php

namespace App\Services;


use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LockService
{
    protected $url;
    protected $salt;



    public function __construct()
    {
        $this->url = config('services.lock.url');
        $this->salt = config('services.lock.salt');
    }

    protected function makeRequest(string $uri, array $params = [], $headers = [], $type = 'form')
    {

        $response = Http::withHeaders($headers);

        if($type === 'json') {
            $response = $response->asJson();
        }else{
            $response = $response->asForm();
        }

        $response = $response->post($uri, $params);

        return $this->parseResponse($response);
    }

    protected function parseResponse(\Illuminate\Http\Client\Response $response)
    {

        if ($response->successful()) {
            $response =  $response->json();

            if(!$response || !is_array($response)) {
                Log::error($response);
                throw new \Exception('Unknown status error');
            }

            return $response;
        }

        if($response->failed()) {
            Log::error($response);
            throw new \Exception('Api service error with status: '.$response->status().json_encode($response));
        }
    }

    public function passCode(Apartment $apartment, Booking $booking, $source){

        $url = $this->url.'/lock/passcode';
        $startDate = Carbon::createFromDate($booking->entry_date)->format("d.m.Y H:i:s");
        $endDate = Carbon::createFromDate($booking->departure_date)->format("d.m.Y H:i:s");
        $secret = hash('sha256', $apartment.$startDate.$endDate.$this->salt);


        $requestArray = [
            "apartmentId" => $apartment->id,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "secret" => $secret,
            "description" => $apartment->address." ".$apartment->flat." ".$apartment->room_number,
            "source" => $source,
        ];

        try {
            $response = $this->makeRequest($url, $requestArray);
            Log::info("================LOCK=====================");
            Log::info($requestArray);
            Log::info($response);
            echo print_r($response);
            if(!array_key_exists('success', $response)){
                throw new \Exception(json_encode($response));
            }
            if(!$response['success']){
                throw new \Exception($this->parseError($response['error']));
            }
        }catch (\Exception $exception){
            Log::error($exception);
        }

    }





}
