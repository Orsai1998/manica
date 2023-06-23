<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApartmentDetailResource;
use App\Http\Resources\ApartmentResource;
use App\Http\Resources\ApartmentTypeResource;
use App\Http\Resources\LivingConditionResource;
use App\Http\Resources\ResidentialComplexResource;
use App\Models\Apartment;
use App\Models\ApartmentFeedback;
use App\Models\ApartmentType;
use App\Models\FavoriteApartment;
use App\Models\LivingCondition;
use App\Models\ResidentialComplex;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApartmentController extends Controller
{
    public function getApartmentTypes() : JsonResource {
        return ApartmentTypeResource::collection(ApartmentType::all());
    }

    public function getResidentialComplexes() : JsonResource {
        return ResidentialComplexResource::collection(ResidentialComplex::all());
    }

    public function getLivingConditions() : JsonResource {
        return LivingConditionResource::collection(LivingCondition::all());
    }

    public function getApartments(Request $request){

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $apartment_type = $request->input('apartment_type_id') ?? 1;
        $adultAmount = $request->input('adult_amount');
        $childrenAmount = $request->input('children_amount');
        $minPrice = $request->min_price;
        $maxPrice = $request->max_price;
        $residential_complex = $request->input('residential_complex_id');


        $apartment = Apartment::query()->orDoesntHave('bookings')->orWhereHas(
            'bookings', function ($query) use ($endDate, $startDate) {
            $query->whereNotBetween('departure_date', [$startDate,$endDate])->where('status', '!=', 'PAID');
             }
        )->where('apartment_type_id', $apartment_type)
            ->whereHas('prices' , function ($query) use ($minPrice, $maxPrice) {
            $query->where('price','>=', $minPrice)->where('price','<=', $maxPrice);
        })->when($residential_complex, function ($query, $residential_complex)  {
            $query->where('residential_complex_id','=' ,$residential_complex);
        })->get();


        return ApartmentResource::collection($apartment);
    }


    public function getApartmentInfo(Request $request){

        $validator = Validator::make($request->all(), [
            'apartment_id' => 'required | exists:apartments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $apartment = Apartment::find($request->input('apartment_id'));

        return new ApartmentDetailResource($apartment);

    }

    public function getMoreFeedbacks(Request $request){

        $validator = Validator::make($request->all(), [
            'apartment_id' => 'required | exists:apartments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $apartment = Apartment::find($request->input('apartment_id'));

        return ApartmentDetailResource::collection($apartment);

    }


    public function createUpdateApartmentComplex(Request $request){
        $validator = Validator::make($request->all(), [
            'CodeID1' => 'required',
            'GUID' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $complex = ResidentialComplex::where('GUID', $request->GUID)->first();

        try {
            if(!$complex){
                $complex = new ResidentialComplex();
            }
            $complex->GUID = $request->GUID;
            $complex->name = $request->description;
            $complex->code = $request->CodeID1;
            $complex->save();

            Log::info('Apartment complex with GUID '.$request->GUID. ' was created');

            return response()->json([
                'success'=>true,
                'id'=>$complex->id
            ]);
        }catch (\Exception $exception){
            Log::error($exception);
            return response()->json([
                'success'=>true,
                'message'=>$exception->getMessage()
            ]);
        }
    }

    public function createUpdateApartment(Request $request){

        $validator = Validator::make($request->all(), [
            '*.CodeID1' => 'required',
            '*.GUID' => 'required',
            '*.apartmentComplex' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        try {
            foreach ($request->all() as $item){

                    $currentComplex = ResidentialComplex::where('GUID', $item['apartmentComplex']['GUID'])->first();

                    if($currentComplex){

                        $apartment = Apartment::where('GUID', $item['GUID'])->first();
                        if(!$apartment){
                            $apartment = new Apartment();
                        }

                        $apartment->GUID = $item['GUID'];
                        $apartment->residential_complex_id = $currentComplex->id;
                        $apartment->address = $item['address'];
                        $apartment->description = $item['description'];
                        $apartment->longitude = $item['Longitude'];
                        $apartment->latitude = $item['Latitude'];
                        $apartment->room_number = $item['rooms'];
                        $apartment->apartment_type_id = 1;
                        $apartment->save();


                        return response()->json([
                            'success'=>true,
                            'apartment_id'=> $apartment->id
                        ]);
                    }

                return response()->json([
                    'success'=>false,
                    'message'=> 'Apartment complex does not exists'
                ]);
            }
        }catch (\Exception $exception){
            Log::error($exception);
            return response()->json([
                'success'=>false,
                'message'=> $exception->getMessage()
            ]);
        }
    }

    public function toggleFavorite(Request  $request){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'apartment_id' => 'required | exists:apartments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $favorite = FavoriteApartment::where('apartment_id', $request->apartment_id)->where('user_id', $user->id)->first();

        if($favorite){
            $favorite->delete();
        }else{
            FavoriteApartment::create([
                'apartment_id' => $request->apartment_id,
                'user_id' => $user->id,
            ]);

        }

        return response()->json([
            'success'=>true,
        ]);
    }
}
