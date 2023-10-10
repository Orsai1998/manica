<?php

namespace App\Http\Resources;

use App\Models\Payment;
use App\Models\UserPaymentCard;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'booking_id' => $this->id,
            'status' => $this->status() ?? "",
            'apartment' => new ApartmentDetailResource($this->apartments),
            'number_of_adult' => $this->number_of_adult,
            'number_of_children' => $this->number_of_children,
            'is_business_trip_reservation' => $this->is_business_trip_reservation,
            'entry_date' => $this->entry_date,
            'departure_date' => $this->departure_date,
            'days' => $this->numberOfDays(),
            'is_late_departure' => $this->is_late_departure,
            'number_of_nights' => $this->numberOfNights(),
            'total_sum' => $this->total_sum,
            'deposit' => $this->deposit,
            'payment_method' => $this->getPaymentMethod(),
            'payment_details' => $this->payment_details($this->apartment_id, $this->entry_date, $this->departure_date,$this->is_late_departure ),
            'key-lock' => $this->getKeyLock()
        ];
    }


}
