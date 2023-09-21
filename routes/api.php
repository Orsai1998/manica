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



Route::controller(App\Http\Controllers\Auth\AuthController::class)->group(function(){
    Route::post('otp/generate', 'generate')->name('otp.generate');
    Route::post('otp/login', 'loginWithOtp')->name('otp.getlogin');
    Route::post('otp/send', 'sendOtp')->name('otp.send');
});

Route::controller(App\Http\Controllers\Auth\SignUpController::class)->group(function(){
    Route::post('signup/otp_generate', 'sendOtpToRegister')->name('otp.sendOtpToRegister');
    Route::post('signup/otp_verify', 'verifyOtp')->name('otp.verifyOtp');
});

Route::middleware('auth:sanctum')->group(function(){
    Route::get('user', [App\Http\Controllers\UserController::class, 'index'])->name('index');
    Route::post('/user/delete', [App\Http\Controllers\Auth\AuthController::class, 'deleteAcc'])->name('deleteAcc');
    Route::post('/user/update', [App\Http\Controllers\UserController::class, 'update'])->name('update');
    Route::post('/user/avatar_delete', [App\Http\Controllers\UserController::class, 'deleteAvatar'])->name('update');
    Route::post('/user/payDebt', [App\Http\Controllers\UserController::class, 'payDebt'])->name('payDebt');
    Route::post('user/add_card', [App\Http\Controllers\UserController::class, 'addUserPaymentCard']);
    Route::get('user/getUserDebt', [App\Http\Controllers\UserController::class, 'getUserDebt']);
    Route::post('user/set_default_card', [App\Http\Controllers\UserController::class, 'setDefaultCard']);
    Route::post('user/delete_card', [App\Http\Controllers\UserController::class, 'deletePaymentCard']);
    Route::post('user/delete_document', [App\Http\Controllers\UserController::class, 'deleteDocument']);
    Route::post('signup', [App\Http\Controllers\Auth\SignUpController::class, 'signUp'])->name('signUp');
    Route::post('logout', [App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('logout');

    Route::group(['prefix'=>'booking'], function(){
        Route::post('/create',  [App\Http\Controllers\BookingController::class, 'create']);
        Route::post('/pay',  [App\Http\Controllers\BookingController::class, 'pay']);
        Route::get('/get',  [App\Http\Controllers\BookingController::class, 'getUserBookings']);
        Route::post('/detail',  [App\Http\Controllers\BookingController::class, 'getBookingDetail']);
        Route::post('/renewal',  [App\Http\Controllers\BookingController::class, 'renewalBooking']);
        Route::post('/cancel',  [App\Http\Controllers\BookingController::class, 'cancelBooking']);
    });
    Route::group(['prefix'=>'company_info'], function(){
        Route::post('/create',  [App\Http\Controllers\CompanyInfoController::class, 'create']);
        Route::get('/index',  [App\Http\Controllers\CompanyInfoController::class, 'index']);
        Route::post('/delete',  [App\Http\Controllers\CompanyInfoController::class, 'delete']);
    });
    Route::post('/apartment/toggle_favorite',  [App\Http\Controllers\ApartmentController::class, 'toggleFavorite']);
    Route::get('/apartments/favorite',  [App\Http\Controllers\ApartmentController::class, 'getFavoriteApartments']);
    Route::post('/apartments/add_feedback',  [App\Http\Controllers\ApartmentController::class, 'addFeedback']);
});
Route::get('/apartments/faq',  [App\Http\Controllers\ApartmentController::class, 'faq']);
Route::get('/common/faq',  [App\Http\Controllers\CommonController::class, 'getQuestionAnswer']);
Route::get('/common/cities',  [App\Http\Controllers\CommonController::class, 'getCities']);
Route::get('/common/docs',  [App\Http\Controllers\CommonController::class, 'getOrganizationDocuments']);

Route::get('/apartment',  [App\Http\Controllers\ApartmentController::class, 'getApartmentInfo']);
Route::get('/apartment_feedbacks',  [App\Http\Controllers\ApartmentController::class, 'getMoreFeedbacks']);

Route::group(['prefix'=>'filter'], function(){

    Route::get('/apartment_types',  [App\Http\Controllers\ApartmentController::class, 'getApartmentTypes']);
    Route::get('/apartments',  [App\Http\Controllers\ApartmentController::class, 'getApartments']);
    Route::get('/residential_complexes',  [App\Http\Controllers\ApartmentController::class, 'getResidentialComplexes']);
    Route::get('/living_conditions',  [App\Http\Controllers\ApartmentController::class, 'getLivingConditions']);
});
Route::post('/payment_response',  [App\Http\Controllers\PaymentController::class, 'paymentResponse']);

