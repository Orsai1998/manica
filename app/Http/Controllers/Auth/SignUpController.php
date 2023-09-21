<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendUserDocuments;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\VerificationCode;
use App\Services\IntegrationOneCService;
use App\Traits\UserExtension;
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
    protected $integrationService;
    use UserExtension;

    public function __construct(IntegrationOneCService $integrationService)
    {
        $this->integrationService = $integrationService;
    }
    public function sendOtpToRegister(Request $request)
    {

        $user = User::where('phone_number', $request->phone_number)->first();

        if($user){
            return response()->json([
                'success'=>false,
                'message'=>'Пользователь существует'
            ]);
        }
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required'
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

        $now = now();

        return VerificationCode::create([
            'user_id' => 0,
            'phone_number' => $phone_number,
            'code' => rand(1234, 9999),
            'expire_at' => $now->addMinutes(5)
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required',
            'code' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $user = VerificationCode::where('phone_number', $request->phone_number)
            ->where('code', $request->code)->latest()
            ->first();
        $now = now();
        if (!$now->isBefore($user->expire_at)) {
            return response()->json([
                'success'=>false,
                'message' => 'Invalid OTP'
            ], 400);
        }

        // OTP is valid, perform further actions like login or account activation
        $role = Role::where('name','client')->first();
        $userPhone = User::withTrashed()->where('phone_number', $request->phone_number)->first();

        if($userPhone){
            if($userPhone->trashed()){
                $userPhone->restore();
                return $this->respondWithToken($userPhone->createToken('TOKEN')->plainTextToken);
            }
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => '',
                'email' => $request->phone_number.'@mail.ru',
                'guid' => (string) Str::uuid(),
                'phone_number' => $request->phone_number,
                'email_verified_at' => now(),
                'birth_date' => now(),
                'role_id' => $role->id,
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);
            $this->integrationService->createUpdateUser($user);
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            return response()->json([
                'success'=>false,
                'message' => $exception->getMessage()
            ]);
        }
        return $this->respondWithToken($user->createToken('TOKEN')->plainTextToken);

    }

    public function signUp(Request $request){

        $user = Auth::user();
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'isFemale' => 'required',
            'birth_date' => 'required',
            'front_ID' => 'required',
            'back_ID' => 'required',
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

            if($request->hasFile('front_ID')){
                $frontId = $request->file('front_ID');
                $this->saveUserDocument(1,$frontId,$user);

            }
            if($request->hasFile('back_ID')){
                $backId = $request->file('back_ID');
                $this->saveUserDocument(0,$backId,$user);
            }

            $user->name = $request->input('name');
            $user->isFemale = $request->input('isFemale');
            $user->birth_date = Carbon::createFromDate($request->input('birth_date'))->format("y.m.d");
            $user->save();
            $client = User::find($user->id);


            if(empty($user->one_c_guid)){
                $this->integrationService->createUpdateUser($client);
                SendUserDocuments::dispatch($user, $this->integrationService);
            }


            DB::commit();
            return response()->json([ 'success'=> true], 200);

        } catch (\Exception $exception){
            DB::rollBack();
            return response()->json([ 'success'=> false, 'message' => $exception->getMessage()], 400);
        }
    }



    protected function createUserDocument($user_id, $path, $name,$isFront){
        UserDocument::create([
            'user_id' => $user_id,
            'path' => $path,
            'name' => $name,
            'isFrontSide' => $isFront
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
