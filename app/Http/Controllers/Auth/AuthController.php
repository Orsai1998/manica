<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function now;
use function response;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        /* Validate Data */
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|exists:users,phone_number'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'error_msg'=>$validator->errors()
            ]);
        }
        /* Generate An OTP */
        $userOtp = $this->generateOtp($request->phone_number);
        //$userOtp->sendSMS($request->phone_number);

        return response()->json([
            'success' => 'true',
            'Код отправлен'
        ]);
    }


    public function generateOtp($phone_number)
    {
        $user = User::where('phone_number', $phone_number)->first();

        /* User Does not Have Any Existing OTP */
        $userOtp = VerificationCode::where('user_id', $user->id)->latest()->first();

        $now = now();

        if($userOtp && $now->isBefore($userOtp->expire_at)){
            return $userOtp;
        }

        /* Create a New OTP */
        return VerificationCode::create([
            'user_id' => $user->id,
            'otp' => rand(123456, 999999),
            'expire_at' => $now->addMinutes(1000)
        ]);
    }

    public function loginWithOtp(Request $request)
    {
        /* Validation */
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|exists:users,phone_number',
            'otp' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'error_msg'=>$validator->errors()
            ]);
        }

        /* Validation Logic */
        $userOtp = VerificationCode::where('phone_number', $request->phone_number)->where('otp', $request->otp)->first();

        $now = now();
        if (!$userOtp) {

            return response()->json([
                'success' => 'false',
                'Введенный код не верен'
            ]);
        }else if($userOtp && $now->isAfter($userOtp->expire_at)){
            return response()->json([
                'success' => 'false',
                'Введенный код просрочен'
            ]);
        }

        $user = User::where('phone_number', $request->phone_number)->first();

        if($user){

            $userOtp->update([
                'expire_at' => now()
            ]);

            return $this->respondWithToken($user->createToken('TOKEN')->plainTextToken);
        }
        return response()->json([
            'success' => false,
            'Пользователь не найден'
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
