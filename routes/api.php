<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(App\Http\Controllers\Auth\AuthController::class)->group(function(){
    Route::post('otp/generate', 'generate')->name('otp.generate');
    Route::post('otp/login', 'loginWithOtp')->name('otp.getlogin');
});

Route::controller(App\Http\Controllers\Auth\SignUpController::class)->group(function(){
    Route::post('signup/otp_generate', 'sendOtpToRegister')->name('otp.sendOtpToRegister');
    Route::post('signup/otp_verify', 'verifyOtp')->name('otp.verifyOtp');
});

Route::middleware('auth:sanctum')->group(function(){
    Route::post('signup', [App\Http\Controllers\Auth\SignUpController::class, 'signUp'])->name('signUp');
});

Route::get('/apartment',  [App\Http\Controllers\ApartmentController::class, 'getApartmentInfo']);

Route::group(['prefix'=>'filter'], function(){
    Route::get('/apartment_types',  [App\Http\Controllers\ApartmentController::class, 'getApartmentTypes']);
    Route::get('/apartments',  [App\Http\Controllers\ApartmentController::class, 'getApartments']);
    Route::get('/residential_complexes',  [App\Http\Controllers\ApartmentController::class, 'getResidentialComplexes']);
    Route::get('/living_conditions',  [App\Http\Controllers\ApartmentController::class, 'getLivingConditions']);
});
