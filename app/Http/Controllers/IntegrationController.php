<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ApartmentPrice;
use App\Models\ApartmentState;
use App\Models\ResidentialComplex;
use App\Models\User;
use App\Models\UserDebt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IntegrationController extends Controller
{
    public function createApartmentPrices(Request $request){
        info("=======PRICES==========");
        info($request->all());

        $validator = Validator::make($request->all(), [
            '*.GUID' => 'required',
            '*.priceList' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        try {
            foreach ($request->all() as $item){

                $apartment= Apartment::where('GUID', $item['GUID'])->first();

                if(!$apartment){
                    Log::error('Apartment  with GUID '.$item['GUID']. ' was not found');
                }else{
                    if(!empty($item['priceList'])){

                        foreach($item['priceList'] as $price){
//                            $datePrice = Carbon::createFromDate($price['period'])->format('Y-m-d');
                            $apartmentPrice = ApartmentPrice::where('apartment_id',  $apartment->id)
                                ->whereDate('date', $price['period'])->first();
                            if(!$apartmentPrice){
                                $apartmentPrice = new ApartmentPrice();
                            }
                            $apartmentPrice->apartment_id =  $apartment->id;
                            $apartmentPrice->price =  $price['price'];
                            $apartmentPrice->state =  1;
                            $apartmentPrice->date =  $price['period'];
                            $apartmentPrice->save();

                        }
                    }else{
                        Log::error('Apartment with GUID '.$item['GUID']. ' was with empty price list');
                    }
                }
            }

            return response()->json([
                'success'=>true,
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'success'=>false,
                'message'=>$exception->getMessage()
            ]);
        }

    }

    public function createApartmentStates(Request $request){

        Log::info("==========Create apartment state===========");
        $validator = Validator::make($request->all(), [
            '*.GUID' => 'required',
            '*.states' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }
        Log::info($request->all());

        try {
            foreach ($request->all() as $item){

                $apartment= Apartment::where('GUID', $item['GUID'])->first();

                if(!$apartment){
                    Log::error('Apartment complex with GUID '.$item['GUID']. ' was not found');
                }else{
                    if(!empty($item['states'])){

                        foreach($item['states'] as $price){

                            ApartmentState::create([
                                'apartment_id' => $apartment->id,
                                'state' => $price['state'],
                                'date' => $price['period'],
                            ]);
                        }
                    }else{
                        Log::error('Apartment with GUID '.$item['GUID']. ' was with empty price list');
                        return response()->json([
                            'success'=>false,
                            'message' => 'Apartment with GUID '.$item['GUID']. ' was with empty states'
                        ]);
                    }
                }
            }
            Log::info("==========Create apartment state END===========");
            return response()->json([
                'success'=>true,
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'success'=>false,
                'message'=>$exception->getMessage()
            ]);
        }

    }



    public function createUserDebt(Request $request){
        UserDebt::query()->truncate();
        try {
            foreach ($request->all() as $item){

                if(empty($item['clientID'])){
                    return response()->json([
                        'success'=>false,
                        'message'=>'Client id not found'
                    ]);
                }
                if(empty($item['apartmentID'])){
                    return response()->json([
                        'success'=>false,
                        'message'=>'apartmentID id not found'
                    ]);
                }
                if(empty($item['paymentType'])){
                    return response()->json([
                        'success'=>false,
                        'message'=>'paymentType not found'
                    ]);
                }
                if(empty($item['balance'])){
                    return response()->json([
                        'success'=>false,
                        'message'=>'balance not found'
                    ]);
                }
                $apartment= Apartment::where('GUID', $item['apartmentID'])->first();
                $user = User::where('guid', $item['clientID'])->first();
                if(!$apartment){
                    return response()->json([
                        'success'=>false,
                        'message'=>'Apartment not found'
                    ]);
                }
                if(!$user){
                    return response()->json([
                        'success'=>false,
                        'message'=>'User not found'
                    ]);
                }
                /*****SAVE in DB START*****/

                $user_debt = UserDebt::where('client_id', $item['clientID'])
                    ->where('apartment_id',$apartment->id)
                    ->where('balance',$item['balance'])
                    ->where('paymentType',$item['paymentType'])
                    ->first();

                if(!$user_debt){
                    $user_debt = new UserDebt();
                }

                $user_debt->user_id = $user->id;
                $user_debt->client_id = $item['clientID'];
                $user_debt->apartment_guid = $item['apartmentID'];
                $user_debt->apartment_id = $apartment->id;
                $user_debt->paymentType = $item['paymentType'];
                $user_debt->balance = $item['balance'];
                $user_debt->needToPay = 0;

                if($item['balance'] < 0 && $item['paymentType'] == 'accommodation'){
                    $user_debt->needToPay = 1;
                }
                if($item['balance'] > 0 && $item['paymentType'] == 'depozit'){
                    $user_debt->needToPay = 1;
                }

                $user_debt->save();
            }
            return response()->json([
                'success'=>true,
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'success'=>false,
                'message' => $exception->getMessage()
            ], 400);
        }


    }
}
