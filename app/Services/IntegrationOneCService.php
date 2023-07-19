<?php

namespace App\Services;


use App\Models\Booking;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

class IntegrationOneCService
{
    protected $url;
    protected $username;
    protected $password;
    protected $client;


    public function __construct()
    {
        $this->url = config('services.one_c.url');
        $this->username = config('services.one_c_username.username');
        $this->password = config('services.one_c_password.create_payment_url');
    }

    protected function makeRequest(string $uri, array $params = [], $isAuthRequired = false)
    {

        if($isAuthRequired){
            $response = Http::withBasicAuth($this->username, $this->password);
        }
        $response = Http::asForm();
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
            throw new \Exception('Api service error with status: '.$response->status(), $response->status());
        }
    }

    public function createUpdateUser(User $user){

        $url = $this->url.'/create_update_user';

        $requestArray = [
            'clientID' => "b09569e8-dda7-11ed-9ee5-8888888887",
            'description' => "test123",
            'bdate' => $user->birth_date,
            'sex' => $user->isFemale ? "f" : "m",
            'phoneNumber' => $user->phone_number,
            'firstName' => $user->name,
            'lastName' => "",
            'middleName' => "",
        ];

        try {
            $response = $this->makeRequest($url, $requestArray);

            if($response['success']){
                $user->guid =  $response['GUID'];
                $user->save();
            }

        }catch (\Exception $exception){
            Log::error($exception);
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
             "checkOut" =>  $booking->depature_date,
             "note" =>  "Бронь №".$booking->id,
        ];

        try {
            $response = $this->makeRequest($url, $requestArray);
            if($response['success']){
                $booking->one_c_guid = $response['id'];
                $booking->save();
            }
        }catch (\Exception $exception){
            Log::error($exception);
        }
    }

    public function createPaymentDeposit(Booking $booking, User $user, $deposit){
        $url = $this->url.'/create_payment';
        $requestArray = [
            'client' => [
                "description" => $user->name,
                "clientID" => $user->guid,
                "phoneNumber" => $user->phone_number
            ],
            "apartmentID" =>  $booking->apartments->GUID,
            "date" =>  $booking->entry_date,
            "paymentType" =>  "depozit",
            "summ" =>  $deposit,
            "reservationId" => $booking->one_c_guid,
            "note" =>  "depozit",
            "transactionID" =>  $booking->one_c_guid,
        ];

        try {
           $this->makeRequest($url, $requestArray);
        }catch (\Exception $exception){
            Log::error($exception);
        }

    }

    public function changeBooking(Booking $booking, User $user){
        $url = $this->url.'/create_payment';
        $requestArray = [
            'client' => [
                "description" => $user->name,
                "clientID" => $user->guid,
                "phoneNumber" => $user->phone_number
            ],
            "apartmentID" =>  $booking->apartments->GUID,
            "reservationId" => $booking->one_c_guid,
            "checkIn" =>  $booking->entry_date,
            "checkOut" =>  $booking->depature_date,
            "note" =>  "Изменение брони №".$booking->id,
        ];

        try {
            $this->makeRequest($url, $requestArray);
        }catch (\Exception $exception){
            Log::error($exception);
        }
    }

    public function addUserDocuments(Request $request){

        $url = $this->url.'/send_client_document';
        if ($request->hasFile('documents')) {
            $requestArray = array();
            foreach ($request->file('documents') as $key => $document) {
                $requestArray['image'.$key] = $document;
            }

            try {
                $this->makeRequest($url, $requestArray);
            }catch (\Exception $exception){
                Log::error($exception);
            }

        }
    }

    public function cancelBooking(Booking $booking){

        if($booking->one_c_guid){
            $url = $this->url.'/cancel_reservation';

            $requestArray = [
                "reservationId" => $booking->one_c_guid,
            ];

            try {
                $this->makeRequest($url, $requestArray);
            }catch (\Exception $exception){
                Log::error($exception);
            }
        }
    }





}
