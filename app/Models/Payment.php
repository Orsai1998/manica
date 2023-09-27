<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class Payment extends Model
{
    use HasFactory;
    protected $table = 'payments';
    protected $guarded = [];


    public function setToken($token){

        $this->payment_token = $token;
        $this->save();
    }

    public function setSuccessStatus(){

        $this->status = 'PAID';
        $this->save();
    }
    public function setCanceled(){
        $this->status = 'CANCELED';
        $this->save();
    }

    public function setErrorStatus(){

        $this->status = 'error';
        $this->save();
    }

    public function bookings(){
        return $this->belongsTo(Booking::class,'booking_id','id');
    }
    public function scopePaid(Builder $query){
        return $query->where('status','=','PAID')->orWhere('status','=','successful');
    }

    public function scopeExpired(){
        $this->bookings()->whereDate('departure_date','<' ,now());
    }

    public function scopeHistory(Builder $query){
        return $query->whereIn('status',['PAID', 'successful'])
            ->where('paymentType','=', 'accommodation')
            ->where('paymentType','!=', 'ADD_CARD');

    }

    public function payment_method(){

        return $this->hasOne(UserPaymentCard::class,"id","user_card_id");
    }
    public function apartment(){
        return $this->hasOne(Apartment::class,'id','apartment_id');
    }
}
