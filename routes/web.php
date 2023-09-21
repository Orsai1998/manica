<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth.basic')->group(function(){
    Route::group(['prefix'=>'api'], function(){
        Route::group(['prefix'=>'integration'], function() {
            Route::post('/create_update_apartment_complex', [App\Http\Controllers\ApartmentController::class, 'createUpdateApartmentComplex']);
            Route::post('/create_update_apartment', [App\Http\Controllers\ApartmentController::class, 'createUpdateApartment']);
            Route::post('/create_price_list', [App\Http\Controllers\IntegrationController::class, 'createApartmentPrices']);
            Route::post('/create_apartment_states', [App\Http\Controllers\IntegrationController::class, 'createApartmentStates']);
            Route::post('/create_user_debts', [App\Http\Controllers\IntegrationController::class, 'createUserDebt']);
        });
    });


});
