<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ApartmentPrice;
use App\Models\ApartmentState;
use App\Models\ResidentialComplex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IntegrationController extends Controller
{
    public function createApartmentPrices(Request $request){


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
                    Log::error('Apartment complex with GUID '.$item['GUID']. ' was not found');
                    return response()->json([
                        'success'=>false,
                        'message' => 'Apartment complex with GUID '.$item['GUID']. ' was not found'
                    ]);
                }else{
                    if(!empty($item['priceList'])){

                        foreach($item['priceList'] as $price){
                            $apartmentPrice = ApartmentPrice::where('apartment_id',  $apartment->id)
                                ->where('date', $price['period'])->get();
                            if(empty($apartmentPrice)){
                                ApartmentPrice::create([
                                    'apartment_id' => $apartment->id,
                                    'price' => $price['price'],
                                    'state' => 1,
                                    'date' => $price['period'],
                                ]);
                            }

                        }
                    }else{
                        Log::error('Apartment with GUID '.$item['GUID']. ' was with empty price list');
                        return response()->json([
                            'success'=>false,
                            'message' => 'Apartment with GUID '.$item['GUID']. ' was with empty price list'
                        ]);
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

        try {
            foreach ($request->all() as $item){

                $apartment= Apartment::where('GUID', $item['GUID'])->first();

                if(!$apartment){
                    Log::error('Apartment complex with GUID '.$item['GUID']. ' was not found');
                    return response()->json([
                        'success'=>false,
                        'message' => 'Apartment complex with GUID '.$item['GUID']. ' was not found'
                    ]);
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
}
