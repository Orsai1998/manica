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
            'organization_name' => $this->residential_complex->organization->name,
            'room_number' => $this->room_number,
            'flat_number' => $this->flat,
            'price' => $this->price(),
            'is_favourite' => 0,
            'rate' => $this->rate()
        ];
    }
}
