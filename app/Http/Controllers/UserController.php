<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\UserPaymentCard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function index() : JsonResource{

        $user = Auth::user();

        return new UserResource($user);
    }

    public function update(Request $request){

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'isFemale' => 'required',
            'birth_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $name = $request->input('name');
        $isFemale = $request->input('isFemale');
        $birth_date = $request->input('birth_date');

        if($request->hasFile('avatar')){
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpg,png,jpeg,gif',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success'=>false,
                    'message'=>$validator->errors()
                ]);
            }

            $avatar = $request->file('avatar');
            $avatarFileName = $avatar->getClientOriginalName();
            $avatarPath = $avatar->storeAs('avatar', $avatarFileName, 'public');

            if(!empty($avatarPath)){
                $user->avatar = $avatarPath;
            }

        }

        $user->name = $name;
        $user->isFemale = $isFemale;
        $user->birth_date = $birth_date;
        $user->save();

        return response()->json([
            'success'=>true,
        ]);
    }

    public function addUserPaymentCard(Request $request){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'card_holder' => 'required',
            'card_number' => 'required',
            'card_cvv' => 'required',
            'card_exp_date' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }


        try {
            $payment_card = new UserPaymentCard();
            $payment_card->user_id = $user->id;
            $payment_card->last_digits = '8919';
            $payment_card->token = 'test';
            $payment_card->exp_date = '11/25';
            $payment_card->save();

            return response()->json([
                'success'=>true,
            ]);

        }catch (\Exception $exception){

            return response()->json([
                'success'=>false,
                'message'=> $exception->getMessage()
            ]);
        }
    }

}
