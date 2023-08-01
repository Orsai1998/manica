<?php

namespace App\Services;


use App\Models\Booking;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
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
        }else{
            echo 'Нет документов';
        }

    }

    public function createUpdateUser(User $user){

        $url = $this->url.'/create_update_user';
        $requestArray = [
            'clientID' => $user->guid,
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

            if(!array_key_exists('success', $response)){
                throw new \Exception(json_encode($response));
            }
            if(!$response['success']){
                throw new \Exception($this->parseError($response['error']));
            }
            $user->one_c_guid =  $response['GUID'];
            $user->save();

            $this->sendUserDocuments($user);

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
            Log::info($response);
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
            $response = $this->makeRequest($url, $requestArray);
            if(!$response['success']){
                throw new \Exception($this->parseError($response['error']));
            }
        }catch (\Exception $exception){
            Log::error($exception);
            throw new \Exception($exception->getMessage());
        }
    }

    public function addUserDocuments($image, $clientId){

        $url = $this->url.'/send_client_document';

        try {
            $response = Http::withBasicAuth($this->username, $this->password)->withBody(
                file_get_contents($image->path()), // Content of the image
                $image->getClientOriginalName(),   // Original image name
                'image/jpeg'                       // MIME type of the image (change as per your image type)
            )->withOptions([
                'multipart' => true, // Set the request as a multipart/form-data request
            ])->post($url, [
                'clientId' => $clientId,           // Additional key-value pair
            ]);

            Log::info($response);
        }catch (\Exception $exception){
           throw $exception;
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
