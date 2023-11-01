<?php

namespace App\Traits;

use App\Models\ApartmentPrice;
use App\Models\Booking;
use App\Models\UserDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait BookingTrait
{

    public function calculateBookingSumForCancel(Booking $booking){

        $refund_amount = 0;
        $departure_date= Carbon::createFromDate($booking->departure_date);

        if($departure_date->isCurrentDay() || $departure_date->isDayOfWeek(5) ||
            $departure_date->isDayOfWeek(6)){
            return $refund_amount;
        }

        if($departure_date->isAfter($departure_date->addDays(3))){
            $entry_date =  Carbon::createFromDate($booking->entry_date);
            $days = $departure_date->diffInDays($entry_date);
            $dayly_amount = abs($booking->total_sum/$days);
            return $booking->total_sum - $dayly_amount;
        }

    }

    public function payment_details($apartment_id, $start_date, $end_date, $is_late_departure = false){

        $payment_details = [];
        $start_date = Carbon::createFromDate($start_date)->timezone(config('app.timezone'));
        $end_date = Carbon::createFromDate($end_date)->timezone(config('app.timezone'));

        $apartment_price = ApartmentPrice::select('price', DB::raw('count(*) as total'))
            ->where('apartment_id', $apartment_id)->
            whereDate('date','>=',$start_date)->
            whereDate('date','<', $end_date)
            ->groupBy('price')
            ->pluck('total','price')
            ->toArray();

        $price = 0;
        $name = "";

        $apartment_price_for_late = 0;
        if($is_late_departure){
            $apartment_price_for_late = ApartmentPrice::where('apartment_id', $apartment_id)
                ->whereDate('date',$end_date)->first();
            $apartment_price_for_late = $apartment_price_for_late->price / 2;
            $name .= "За поздний выезд " . $apartment_price_for_late . "; ";
        }

        foreach ($apartment_price as $key => $item){
            $name .= $item. " x ". $key."; ";
            $price += $item * $key;
        }
        $price = $price + $apartment_price_for_late;
        $deposit = config('services.deposit');

        $payment_details[] = [
            'name' => $name,
            'price' => $price,
            'type' => 'accommodation',

        ];

        $payment_details[] = [
            'name' => 'Депозит',
            'price' => $deposit,
            'type' => 'deposit',
        ];

        $payment_details[] = [
            'name' => 'Итоговая сумма ',
            'price' => $deposit + $price,
            'type' => 'total_sum',
        ];

        return $payment_details;
    }

    public function calculateForRenewal($new_departure_date, $is_late_departure){
        $apartment_price = ApartmentPrice::select('price', DB::raw('count(*) as total'))
            ->where('apartment_id', $this->apartment_id)->
            where('date','>',$this->departure_date)->
            where('date','<=',$new_departure_date)
            ->groupBy('price')
            ->pluck('total','price')
            ->toArray();

        $price = 0;
        $name = "";
        $apartment_price_for_late = 0;
        if($is_late_departure){
            $apartment_price_for_late = ApartmentPrice::where('apartment_id', $this->apartment_id)
                ->whereDate('date',$new_departure_date)->first();
            $apartment_price_for_late = $apartment_price_for_late->price / 2;
            $name .= "За поздний выезд " . $apartment_price_for_late . "; ";
        }

        foreach ($apartment_price as $key => $item){
            $name .= $item. " x ". $key."; ";
            $price += $item * $key;
        }
        return $price + $apartment_price_for_late;
    }


}

