<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'photo' => $this->mainPhoto(),
            'residential_complex_name' => $this->residential_complex->name,
            'organization_name' => $this->residential_complex->organization->name ?? 'Manica',
            'room_number' => $this->room_number,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'flat_number' => $this->flat,
            'floor' => $this->floor,
            'price' => $this->getPriceForSpecificDate($request->input('start_date')) ?? '',
            'is_favourite' => $this->is_favorite(),
            'is_available' => $this->is_available,
            'rate' => $this->feedbacks_avg_rate
        ];
    }
}
