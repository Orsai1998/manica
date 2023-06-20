<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserBookingsResource extends JsonResource
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
            'photo' => $this->apartments->mainPhoto(),
            'residential_complex_name' => $this->apartments->residential_complex->name,
            'entry_date' => $this->entry_date,
            'departure_date' => $this->departure_date,
        ];
    }
}
