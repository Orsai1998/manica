<?php

namespace App\Services;


use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IntegrationOneCService
{
    protected $url;
    protected $username;
    protected $password;
    protected $client;


    public function __construct()
    {
        $this->url = config('services.one_c.url');
        $this->username = config('services.one_c.username');
        $this->password = config('services.one_c.password');
    }

    protected function makeRequest(string $uri, array $params = [], $isAuthRequired = false)
    {

        $response = Http::withBasicAuth($this->username, $this->password);
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

    public function sendUserDocuments(User $user){

        $url = $this->url.'/send_client_document';
        if($user->getDocumentPhoto('front') && $user->getDocumentPhoto('back')){
            $requestArray = [
                "clientId" => $user->guid,
                "front_ID" => [
                    "name" => $user->getDocumentPhoto('front')->name,
                    "url" => asset('storage/'.$user->getDocumentPhoto('front')->path),
                ],
                "back_ID" => [
                    "name" => $user->getDocumentPhoto('back')->name,
                    "url" => asset('storage/'.$user->getDocumentPhoto('back')->path),
                ]
            ];

            try {
                $response = $this->makeRequest($url, $requestArray);

               echo print_r($response);
                if(!array_key_exists('success', $response)){
                    throw new \Exception(json_encode($response));
                }
                if(!$response['success']){
                    throw new \Exception($this->parseError($response['error']));
                }


            }catch (\Exception $exception){
                Log::error($exception);
                throw new \Exception($exception->getMessage());
            }
        }

    }

    public function createUpdateUser(User $user){

        $url = $this->url.'/create_update_user';
        $requestArray = [
            'clientID' => $user->guid,
            'description' => $user->name. " ".$user->phone_number,
            'bdate' => $user->birth_date,
            'sex' => $user->isFemale ? "f" : "m",
            'phoneNumber' => $user->phone_number,
            'firstName' => $user->name,
            'lastName' => "",
            'middleName' => "",
        ];

        try {
            $response = $this->makeRequest($url, $requestArray);
            Lon:info($response);
            if(!array_key_exists('success', $response)){
                throw new \Exception(json_encode($response));
            }
            if(!$response['success']){
                if($response['clientID']){
                    
                }
                throw new \Exception($this->parseError($response['error']));
            }
            if(!$response['GUID'] && empty($response['GUID'])){
                throw new \Exception('Ошибка 1С не передан GUID');
            }
            $user->one_c_guid =  $response['GUID'];
            $user->save();



        }catch (\Exception $exception){
            Log::error($exception);
            throw new \Exception($exception->getMessage());
        }
    }


    public function createBooking(Booking $booking, User $user){

        $url = $this->url.'/create_reservation';
        $requestArray = [
            'client' => [
                "description" => $user->name,
                "clientID" => $user->guid,
                "phoneNumber" => $user->phone_number
            ],
             "apartmentID" =>  $booking->apartments->GUID,
             "checkIn" =>  $booking->entry_date,
             "checkOut" =>  $booking->departure_date,
             "note" =>  "Бронь №".$booking->id,
        ];

        try {

            $response = $this->makeRequest($url, $requestArray);
            if(!array_key_exists('success', $response)){
                throw new \Exception(json_encode($response));
            }
            if(!$response['success']){
                throw new \Exception($this->parseError($response['error']));
            }
            $booking->one_c_guid = $response['GUID'];
            $booking->save();

        }catch (\Exception $exception){
            Log::error($exception);
            throw new \Exception($exception->getMessage());
        }
    }

    public function createPayment(Booking $booking, User $user,String $type ,$deposit, $paymentToken){
        $url = $this->url.'/create_payment';
        $requestArray = [
            'client' => [
                "description" => $user->name,
                "clientID" => $user->guid,
                "phoneNumber" => $user->phone_number
            ],
            "apartmentID" =>  $booking->apartments->GUID,
            "date" =>  $booking->entry_date,
            "paymentType" =>  $type,
            "summ" =>  $deposit,
            "reservationId" => $booking->one_c_guid,
            "note" =>  "depozit",
            "transactionID" => $paymentToken,
        ];

        try {
            $response = $this->makeRequest($url, $requestArray);
            if(!array_key_exists('success', $response)){
                throw new \Exception(json_encode($response));
            }
            if(!$response['success']){
                throw new \Exception($this->parseError($response['error']));
            }
        }catch (\Exception $exception){
            Log::error($exception);
            throw new \Exception($exception->getMessage());
        }

    }

    public function changeBooking(Booking $booking, User $user){
        $url = $this->url.'/change_reservation';
        $requestArray = [
            'client' => [
                "description" => $user->name,
                "clientID" => $user->guid,
                "phoneNumber" => $user->phone_number
            ],
            "apartmentID" =>  $booking->apartments->GUID,
            "reservationId" => $booking->one_c_guid,
            "checkIn" => $booking->entry_date,
            "checkOut" =>  $booking->departure_date,
            "note" =>  "Изменение брони №".$booking->id,
        ];

        try {
            $response = $this->makeRequest($url, $requestArray);
            if(!array_key_exists('success', $response)){
                Log::error($response);
                throw new \Exception(json_encode($response));
            }
            if(!$response['success']){
                throw new \Exception($this->parseError($response['error']));
            }
        }catch (\Exception $exception){
            Log::error($exception);
            throw new \Exception($exception->getMessage());
        }
    }



    protected function parseError(Array $response): string
    {
        $messageErr = "";
        for ($i = 0; $i< count($response); $i++){
            $messageErr .= (string)$response[$i] . "; ";
        }

        return $messageErr;
    }

    public function cancelBooking(Booking $booking){

        if($booking->one_c_guid){
            $url = $this->url.'/cancel_reservation';

            $requestArray = [
                "reservationId" => $booking->one_c_guid,
            ];

            try {
                $response = $this->makeRequest($url, $requestArray);
                if(!array_key_exists('success', $response)){
                    Log::error($response);
                    throw new \Exception(json_encode($response));
                }
                if(!$response['success']){
                    throw new \Exception($this->parseError($response['error']));
                }

            }catch (\Exception $exception){
                Log::error($exception);
                throw new \Exception($exception->getMessage());
            }
        }
    }





}
