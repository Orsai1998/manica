<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use function now;
use function response;

class SignUpController extends Controller
{
    public function sendOtpToRegister(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|unique:users,phone_number'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        try{
            $userOtp = $this->generateOtp($request->phone_number);
        }catch (\Exception $exception){
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        $userOtp->sendSMS($request->phone_number);

        return response()->json([
            'success' => 'true',
            'message'=> 'Код отправлен '
        ]);
    }


    public function generateOtp($phone_number)
    {
        $userOtp = VerificationCode::where('phone_number', $phone_number)->latest()->first();

        $now = now();

        if($userOtp && $now->isBefore($userOtp->expire_at)){
            return $userOtp;
        }

        return VerificationCode::create([
            'user_id' => 0,
            'phone_number' => $phone_number,
            'code' => rand(1234, 9999),
            'expire_at' => $now->addMinutes(1000)
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|exists:verification_codes,phone_number',
            'code' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $user = VerificationCode::where('phone_number', $request->phone_number)
            ->where('code', $request->code)
            ->first();

        if (!$user) {
            return response()->json([
                'success'=>false,
                'message' => 'Invalid OTP'
            ], 401);
        }

        // OTP is valid, perform further actions like login or account activation
        $role = Role::where('name','client')->first();

        $user = User::create([
            'name' => 'TEMP',
            'email' => $request->phone_number.'@mail.ru',
            'phone_number' => $request->phone_number,
            'email_verified_at' => now(),
            'role_id' => $role->id,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ]);

        return $this->respondWithToken($user->createToken('TOKEN')->plainTextToken);

    }

    public function signUp(Request $request){

        $user = Auth::user();

        if(!$user){
            return response()->json([ 'success'=>false, 'message' => 'User not found'], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'isFemale' => 'required',
            'birth_date' => 'required',
            'documents' => 'required | array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        try {
            if($request->hasFile('avatar')){
                $avatar = $request->file('avatar');
                $avatarFileName = $avatar->getClientOriginalName();
                $avatarPath = $avatar->storeAs('avatar', $avatarFileName, 'public');

                if(!empty($avatarPath)){
                    $user->avatar = $avatarPath;
                }

            }
            if ($request->hasFile('documents')) {

                foreach ($request->file('documents') as $image) {
                    $fileName = $image->getClientOriginalName();
                    $path = $image->storeAs('documents', $fileName, 'public');

                    UserDocument::create([
                        'user_id' => $user->id,
                        'path' => $path
                    ]);

                }
            }else {
                return response()->json([ 'success'=> false, 'message' => 'Documents was not uploaded'], 400);

            }

            $user->name = $request->input('name');
            $user->isFemale = $request->input('isFemale');
            $user->birth_date = Carbon::createFromDate($request->input('birth_date'))->format("y.m.d");
            $user->save();

            return response()->json([ 'success'=> true], 200);

        } catch (\Exception $exception){
            DB::rollBack();
            return response()->json([ 'success'=> false, 'message' => $exception->getMessage()], 400);
        }
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
