<?php

namespace App\Http\Controllers\Auth;

use App\Billing\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Jobs\SendUserDocuments;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\VerificationCode;
use App\Services\IntegrationOneCService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Nette\Schema\ValidationException;
use function now;
use function response;

class SignUpController extends Controller
{
    protected $integrationService;

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
            'phone_number' => 'required|numeric|digits:11'
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
        $now = now();
        if (!$now->isBefore($user->expire_at)) {
            return response()->json([
                'success'=>false,
                'message' => 'Invalid OTP'
            ], 400);
        }

        // OTP is valid, perform further actions like login or account activation
        $role = Role::where('name','client')->first();
        $userPhone = User::where('phone_number', $request->phone_number)->first();

        if($userPhone){
            if($userPhone->trashed()){
                $userPhone->restore();
                return $this->respondWithToken($user->createToken('TOKEN')->plainTextToken);
            }
        }

        $user = User::create([
            'name' => 'TEMP',
            'email' => $request->phone_number.'@mail.ru',
            'guid' => (string) Str::uuid(),
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
                $frontIdName = $frontId->getClientOriginalName().".".$frontId->getExtension();
                $frontIdPath = $frontId->storeAs('documents', $frontIdName, 'public');

                if(!empty($frontIdPath)){
                    $userDoc = UserDocument::where('user_id', $user->id)->where('path', $frontIdPath)->first();
                    if($userDoc){
                        $userDoc->path = $frontIdPath;
                        $userDoc->save();
                    }else{
                        $this->createUserDocument($user->id, $frontIdPath,$frontIdName);
                    }

                }

            }
            if($request->hasFile('back_ID')){
                $backId = $request->file('back_ID');
                $backIdName = $backId->getClientOriginalName().".".$backId->getExtension();
                $backIdPath = $backId->storeAs('documents', $backIdName, 'public');

                $userDoc = UserDocument::where('user_id', $user->id)->where('path', $backIdPath)->first();
                if($userDoc){
                    $userDoc->path = $backIdPath;
                    $userDoc->save();
                }else{
                    $this->createUserDocument($user->id, $backIdPath,  $backIdName );
                }

            }

            $user->name = $request->input('name');
            $user->isFemale = $request->input('isFemale');
            $user->birth_date = Carbon::createFromDate($request->input('birth_date'))->format("y.m.d");
            $user->save();
            $client = User::find($user->id);
            DB::commit();

            $this->integrationService->createUpdateUser($client);
            SendUserDocuments::dispatch($user, $this->integrationService);

            return response()->json([ 'success'=> true], 200);

        } catch (\Exception $exception){
            DB::rollBack();
            return response()->json([ 'success'=> false, 'message' => $exception->getMessage()], 400);
        }
    }



    protected function createUserDocument($user_id, $path, $type){
        UserDocument::create([
            'user_id' => $user_id,
            'path' => $path,
            'name' => $type
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
