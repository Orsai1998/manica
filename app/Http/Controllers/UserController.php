<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function index() : JsonResource{

        $user = Auth::user();

        return UserResource::collection($user);
    }

    public function update(Request $request){

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'isFemale' => 'required',
            'birth_date' => 'required',
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

}
