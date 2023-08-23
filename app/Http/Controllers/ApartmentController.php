<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApartmentDetailedFeedbackResource;
use App\Http\Resources\ApartmentDetailResource;
use App\Http\Resources\ApartmentResource;
use App\Http\Resources\ApartmentTypeResource;
use App\Http\Resources\LivingConditionResource;
use App\Http\Resources\ResidentialComplexResource;
use App\Models\Apartment;
use App\Models\ApartmentFeedback;
use App\Models\ApartmentType;
use App\Models\Faq;
use App\Models\FavoriteApartment;
use App\Models\LivingCondition;
use App\Models\ResidentialComplex;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $rooms = $request->input('rooms');
        $sortBy = $request->input('sortBy');
        $sort = $request->input('sort') ?? 'asc';
        $minPrice = (integer)$request->min_price;
        $maxPrice = (integer)$request->max_price;
        $residential_complex = $request->input('residential_complex_id');

        if(!$minPrice){
            $minPrice = 1000;
        }
        if(!$maxPrice){
            $maxPrice = 1000000;
        }
        if(!$startDate){
            $startDate = Carbon::now()->format('Y-m-d');
        }
        if(!$endDate){
            $endDate = Carbon::now()->addDays(10)->format('Y-m-d');
        }
        if(!$rooms){
            $rooms = 0;
        }
        if(!$sortBy){
            $minPrice = 1000;
            $maxPrice = 1000000;
            $sortBy = 'apartment_price_intervals.price';
        }
        if($sortBy == 'price'){
            $sortBy = 'apartment_price_intervals.price';
        }

        if($sortBy == 'newest'){
            $sortBy = 'apartments.created_at';
        }
        if($sortBy == 'popular'){
            $sortBy = 'feedbacks_avg_rate';
        }

        if($sortBy == 'for_big_family'){
            $sortBy = 'apartments.room_number';
            $sort = 'desc';
        }

        $apartment = Apartment::query()->orDoesntHave('bookings')->orWhereDoesntHave(
            'bookings', function ($query) use ($endDate, $startDate) {
            $query->whereBetween('departure_date', [$startDate,$endDate])->where('status', '=', 'PAID')
                ->orWhere('status', '=', 'PROCESS')->orWhere('status', '=', 'CANCELED');
             }
        )->where('apartment_type_id', $apartment_type)->where('room_number','>=' ,$rooms)
            ->when($residential_complex, function($query) use ($residential_complex){
                $query->where('residential_complex_id', $residential_complex);
            })->withAvg('feedbacks','rate')
            ->join('apartment_price_intervals' , function ($join) use ($minPrice, $maxPrice, $startDate, $endDate) {
            $join->on('apartments.id','=','apartment_price_intervals.apartment_id');
            $join->where('apartment_price_intervals.price','>=', $minPrice);
            $join->where('apartment_price_intervals.price','<=', $maxPrice);
            $join->where('apartment_price_intervals.start_date',"<=", $startDate);
            $join->where('apartment_price_intervals.end_date',">=",$endDate);
            $join->whereNotNull('apartment_price_intervals.end_date');
        })->join('apartment_availability' , function ($join) use ($minPrice, $maxPrice, $startDate, $endDate) {
                $join->on('apartments.id','=','apartment_availability.apartment_id');
                $join->where('apartment_availability.state','=', 1);
                $join->where('apartment_availability.start_date',"<=", $startDate);
                $join->where('apartment_availability.end_date',">=", $endDate);
                $join->whereNotNull('apartment_availability.end_date');
            })
            ->orderBy($sortBy, $sort)->distinct()->paginate(10);

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

        return new ApartmentDetailedFeedbackResource($apartment);

    }


    public function createUpdateApartmentComplex(Request $request){
        $validator = Validator::make($request->all(), [

            '*.GUID' => 'required',
            '*.description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        try {
        foreach ($request->all() as $item){

            $complex = ResidentialComplex::where('GUID', $item['GUID'])->first();

                if(!$complex){
                    $complex = new ResidentialComplex();
                }
                $complex->GUID = $item['GUID'];
                $complex->name = $item['description'];
                $complex->code = $item['CodeID1'];
                $complex->save();

                Log::info('Apartment complex with GUID '.$item['GUID']. ' was created');

            }
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
            '*.GUID' => 'required',
            '*.apartmentComplex' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }
        $apartments = [];
        try {
            foreach ($request->all() as $item){
                     Log::info("==========Create apartment===========");
                    Log::info($item);
                    $currentComplex = ResidentialComplex::where('GUID', $item['apartmentComplex']['GUID'])->first();

                    if($currentComplex){

                        $apartment = Apartment::where('GUID', $item['GUID'])->first();
                        if(!$apartment){
                            $apartment = new Apartment();
                        }

                        $apartment->GUID = $item['GUID'];
                        $apartment->residential_complex_id = $currentComplex->id;
                        $apartment->address = $item['address'];
                        $apartment->flat = $item['apartmentNumber'];
                        $apartment->floor = $item['floor'];
                        $apartment->block = $item['block'];
                        $apartment->description = $item['description'];
                        $apartment->longitude = $item['Longitude'];
                        $apartment->latitude = $item['Latitude'];
                        $apartment->room_number = $item['rooms'];
                        $apartment->apartment_type_id = 1;
                        $apartment->save();
                        $apartments[] = $apartment->id;



                    }else{
                        Log::error('Apartment complex does not exists '. $item['apartmentComplex']['GUID']);
                    }

            }
            return response()->json([
                'success'=>true,
                'apartments'=> $apartments
            ]);
        }catch (\Exception $exception){
            Log::error($exception);
            return response()->json([
                'success'=>false,
                'message'=> $exception->getMessage()
            ]);
        }
    }

    public function getFavoriteApartments(Request  $request){
        $user = Auth::user();

        $apartments = Apartment::whereHas('favorites', function($q) use($user){
            $q->where('user_id', $user->id);
        })->get();
        return ApartmentResource::collection($apartments);
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

    public function faq(Request $request){

        $validator = Validator::make($request->all(), [
            'apartment_id' => 'required | exists:apartments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $apartment = Apartment::find($request->apartment_id);
        $organization_id = $apartment->residential_complex->organization->id;

        $faq = Faq::where('organization_id', $organization_id)->first();

        if($faq){
            return response()->json([
                'success'=>true,
                'faq' => $faq->faq
            ]);
        }
        return response()->json([
            'success'=>false,
        ]);
    }

    public function addFeedback(Request  $request){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'rate' => 'required',
            'feedback' => 'required',
            'apartment_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $feedback = ApartmentFeedback::where('apartment_id', $request->apartment_id)->where('user_id', $user->id)->first();

        if($feedback){
            return response()->json([
                'success'=>false,
                'message' => 'Вами уже добавлен отзыв'
            ]);
        }
        ApartmentFeedback::create([
            'apartment_id' => $request->apartment_id,
            'user_id' => $user->id,
            'rate' => $request->rate,
            'feedback' => $request->feedback,
        ]);
        return response()->json([
            'success'=>true,
        ]);
    }
}
