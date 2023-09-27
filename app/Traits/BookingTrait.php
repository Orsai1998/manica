<?php

namespace App\Traits;

use App\Models\Booking;
use App\Models\UserDocument;
use Carbon\Carbon;
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


}

