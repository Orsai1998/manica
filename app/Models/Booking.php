<?php

namespace App\Models;

use App\Http\Resources\UserPaymentCardResource;
use App\Traits\BookingTrait;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class Booking extends Model
{
    use HasFactory, BookingTrait;
    protected $guarded = [];


    public function apartments(){
        return $this->hasOne(Apartment::class,'id','apartment_id');
    }
    public function setCanceled(){
        $this->status ='CANCELED';
        $this->save();
        $this->setApartmentAvailable();

    }

    public function setApartmentAvailable(){
        $this->apartments->is_available = 1;
        $this->apartments->save();
    }

    public function calculateTotalSum(){
        $apartment_price = $this->payment_details($this->apartment_id, $this->entry_date, $this->departure_date, false);
        $accommodation_price = 0;
        info($apartment_price);
        foreach ($apartment_price as $item){
            if($item['type'] == 'accommodation'){
                $accommodation_price = $item['price'];
            }
        }
        $deposit = config('services.deposit');

        $this->total_sum = $accommodation_price;
        $this->deposit = $deposit;
        $this->save();

    }


    public function numberOfNights(){
        $date1 = Carbon::parse($this->entry_date);
        $date2 = Carbon::parse($this->departure_date);
        return $date2->diff($date1)->format("%a");
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
        return 0;
    }
    public function getPaymentMethodId(){

        $payment = Payment::where('booking_id', $this->id)->first();

        if($payment){
            $user_card = UserPaymentCard::withTrashed()->where('id', $payment->user_card_id)->first();

            if (!$user_card){
                throw new \Exception('Не найдена платежная карта');
            }
            return $user_card->id;
        }
        return 0;
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
        if(Carbon::createFromDate($this->departure_date)  >= Carbon::now() && $this->status != 'CANCELED'){
            return 'Активная';
        }
        if($this->status == 'CANCELED'){
            return 'Отменен';
        }
        return 'Завершено';
    }
}
