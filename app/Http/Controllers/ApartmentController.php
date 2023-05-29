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
use App\Models\LivingCondition;
use App\Models\ResidentialComplex;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        $apartment_type = $request->input('apartment_type_id');
        $adultAmount = $request->input('adult_amount');
        $childrenAmount = $request->input('children_amount');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $residential_complex = $request->input('residential_complex_id');
        $living_comfort = $request->input('living_comfort_id');

        $apartment = Apartment::all();
        return ApartmentResource::collection($apartment);
    }


    public function getApartmentInfo(Request $request){

        $validator = Validator::make($request->all(), [
            'apartment_id' => 'required | exists:apartments, id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $apartment = Apartment::find($request->input('apartment_id'));

        if(!$apartment){
            return response()->json([
                'success'=>false,
                'message'=> 'Apartment not found'
            ]);
        }
        return ApartmentDetailResource::collection($apartment);

    }

    public function getMoreFeedbacks(Request $request){

        $validator = Validator::make($request->all(), [
            'apartment_id' => 'required | exists:apartments, id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $apartment = Apartment::find($request->input('apartment_id'));


        if(!$apartment){
            return response()->json([
                'success'=>false,
                'message'=> 'Apartment does not have feedbacks'
            ]);
        }

        return ApartmentDetailResource::collection($apartment);

    }
}
