<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApartmentDetailedFeedbackResource;
use App\Http\Resources\ApartmentDetailResource;
use App\Http\Resources\ApartmentResource;
use App\Http\Resources\ApartmentTypeResource;
use App\Http\Resources\LivingConditionResource;
use App\Http\Resources\ResidentialComplexResource;
use App\Models\Apartment;
use App\Models\ApartmentAvailability;
use App\Models\ApartmentFeedback;
use App\Models\ApartmentState;
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
    public function register(){
        return view('register');
    }
    public function do_register(Request $request){
           $email = $request->email;
           $password = $request->password;


    }

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
        $forMap = $request->boolean('is_for_map');
        $apartment_type = $request->input('apartment_type_id') ?? 1;
        $adultAmount = $request->input('adult_amount');
        $childrenAmount = $request->input('children_amount');
        $rooms = collect($request->input('rooms'));
        $sortBy = $request->input('sortBy');
        $sort = $request->input('sort') ?? 'asc';
        $minPrice = (integer)$request->min_price;
        $maxPrice = (integer)$request->max_price;
        $residential_complex = collect($request->input('residential_complex_id'));

        if(!$minPrice){
            $minPrice = 0;
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
        if(empty($rooms)){
            $rooms = 0;
        }
        if(!$sortBy){
            $minPrice = 1000;
            $maxPrice = 1000000;
            $sortBy = 'apartments.is_available';
        }
        if($sortBy == 'price'){
            $sortBy = 'apartment_prices.price';
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

        $apartment = Apartment::query()->WhereDoesntHave(
            'bookings', function ($query) use ($endDate, $startDate) {
            $query->whereBetween('departure_date', [$startDate,$endDate])->where('status', '=', 'PAID')
                ->orWhere('status', '=', 'PROCESS');
             }
        )->where('apartment_type_id', $apartment_type)
           ->withAvg('feedbacks','rate')
            ->Join('apartment_prices' , function ($join) use ($minPrice, $maxPrice, $startDate, $endDate) {
                $join->on('apartments.id','=','apartment_prices.apartment_id');
                $join->whereBetween('apartment_prices.price',[$minPrice, $maxPrice]);
                $join->whereBetween('apartment_prices.date',[$startDate,$endDate]);
                //$join->whereNotNull('apartment_price_intervals.end_date');
        })
            ->Join('apartment_availability' , function ($join) use ($minPrice, $maxPrice, $startDate, $endDate) {
                $join->on('apartments.id','=','apartment_availability.apartment_id');
                $join->where('apartment_availability.state','=', 1);
                $join->whereDate('apartment_availability.start_date','<=',$startDate);
                $join->whereDate('apartment_availability.end_date', '>=', $endDate);
                $join->whereNotNull('apartment_availability.end_date');
            });

        if(count($residential_complex) > 0){
            $apartment = $apartment->join('residential_complexes' , function ($join) use ($residential_complex, $rooms) {
                $join->on('apartments.residential_complex_id','=','residential_complexes.id');
                $join->whereIn('apartments.residential_complex_id',$residential_complex);
            });
        }

        if(count($rooms) > 0){
            $apartment = $apartment->join('apartment_types' , function ($join) use ($rooms) {
                $join->on('apartments.apartment_type_id','=','apartment_types.id');
                $join->whereIn('apartments.room_number',$rooms);
            });
        }

        $apartment = $apartment->orderBy($sortBy, $sort)->get();
        $apartments = collect($apartment)->unique()->pluck('id')->toArray();
        $ids_ordered = implode(',', $apartments);

        $apartment = Apartment::whereIn('id', $apartments)->withAvg('feedbacks','rate') ->orderByRaw("FIELD(id, $ids_ordered)")->paginate(10);
        if($forMap){
            $apartment = Apartment::whereIn('id', $apartments)->withAvg('feedbacks','rate')->get();
        }
        $apartment->date = $startDate;
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
                        $apartment->apartment_type_id = !$item['Penthouse'] ? 1 : 2;
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

    public function getBookedAndAvailableDates(Request $request){

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $apartment_id = $request->apartment_id;

        $dates = [];
        $apartment_availabilities = ApartmentState::where('apartment_id', $apartment_id)
            ->whereDate('date','>=', $startDate)
            ->where('apartment_id','=', $apartment_id)
            ->whereDate('date','<=', $endDate)->get();

        if($apartment_availabilities){
            foreach ($apartment_availabilities as $data){
                $dates[] = [
                    'date' => $data->date,
                    'state' => $data->state
                ];
            }
        }


        $item = [
            'apartment_id' => $apartment_id,
            'dates' => $dates
        ];

        return response()->json([
           'success' => true,
           'data' => $item
        ]);

    }
    public function getFavoriteApartments(Request  $request){
        $user = Auth::user();
        $request->merge(['start_date' => now()->format("Y-m-d")]);
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
