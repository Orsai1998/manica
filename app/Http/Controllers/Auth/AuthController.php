<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use function now;
use function response;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|exists:users,phone_number'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'error_msg'=>$validator->errors()
            ]);
        }

        try {
            $userOtp = $this->generateOtp($request->phone_number);
            $userOtp->sendSMS($request->phone_number);

            return response()->json([
                'success' => 'true',
                'message'=> 'Код отправлен '
            ]);
        }
        catch (\Exception $exception){
            return response()->json([
                'success' => 'false',
                'message'=> $exception->getMessage()
            ]);
        }
    }


    public function generateOtp($phone_number)
    {
        try {
            $user = User::where('phone_number', $phone_number)->first();

            if(!$user){
                throw new \Exception('User not found');
            }
            $userOtp = VerificationCode::where('user_id', $user->id)->latest()->first();
            $now = Carbon::now();

            if($userOtp && $now->isBefore($userOtp->expire_at)){
                return $userOtp;
            }

            return VerificationCode::create([
                'user_id' => $user->id,
                'phone_number' => $phone_number,
                'code' => rand(1000, 9999),
                'expire_at' => $now->addMinutes(10)
            ]);
        }catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }
    }

    public function loginWithOtp(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|exists:users,phone_number',
            'code' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }


        $userOtp = VerificationCode::where('phone_number', $request->phone_number)->where('code', $request->code)->first();

        $now = now();
        if (!$userOtp) {

            return response()->json([
                'success' => 'false',
                'message'=> 'Введенный код не верен'
            ]);
        }else if($now->isAfter($userOtp->expire_at)){
            return response()->json([
                'success' => 'false',
                'message'=>  'Введенный код просрочен'
            ]);
        }

        $user = User::where('phone_number', $request->phone_number)->first();

        if($user){

            $userOtp->update([
                'expire_at' => Carbon::now()
            ]);

            return $this->respondWithToken($user->createToken('TOKEN')->plainTextToken);
        }
        return response()->json([
            'success' => false,
            'message'=> 'Пользователь не найден'
        ]);
    }

    public function deleteAcc(){
        $user = Auth::user();

        $userAcc = User::find($user->id);

        if($userAcc){
            Auth::user()->currentAccessToken()->delete();
            $userAcc->delete();
        }

        return response()->json([
            'success' => true,
        ]);
    }
    public function logout(){
         Auth::user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
        ]);
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => null,
        ]);
    }
}
