<?php

namespace App\Models;

use App\Http\Resources\UserPaymentCardResource;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Booking extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function apartments(){
        return $this->hasOne(Apartment::class,'id','apartment_id');
    }

    public function payments(){
        return $this->belongsTo("App\Models\Payment", "booking1121212_id", "id");
    }
    public function getPaymentMethod(){

        $payment = Payment::where('booking_id', $this->id)->first();

        if($payment){
            $user_card = UserPaymentCard::withTrashed()->where('id', $payment->user_card_id)->first();

            return new UserPaymentCardResource($user_card);

        }
        return "";
    }

    public function getKeyLock(){

        if($this->status == 'PAID'){
            return 9890;
        }

        return "";
    }

    public function numberOfDays(){

        $entry_date = new DateTime($this->entry_date);
        $depature_date = new DateTime($this->departure_date);
        $interval = $entry_date->diff($depature_date);
        $days = $interval->format('%a');

        return $days;
    }

    public function status(){
        if(Carbon::createFromDate($this->depature_date)  >= Carbon::now()){
            return 'Активная';
        }
        return 'Не активная';
    }
}
