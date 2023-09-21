<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserDebt extends Model
{
    use HasFactory;
    protected $table = 'user_debts';
    protected $guarded = [];

    public function apartment(){
        return $this->hasOne(Apartment::class,'id','apartment_id');
    }

    public function booking(){
        return $this->belongsTo(Booking::class, 'apartment_id','apartment_id');
    }

    public function userBooking(){
        $user = Auth::user();
        $booking = $this->booking()->where('user_id', $user->id)->first();
        if($booking){
            return $booking->id;
        }
        return "";
    }

    public function needToPay(){
        if(($this->balance < 0 && $this->paymentType == 'accommodation') ||
            ($this->balance > 0 && $this->paymentType == 'depozit') ){
                return true;
        }
        return false;
    }
}
