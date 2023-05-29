<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApartmentDetailResource extends ResourceCollection
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
            'photos' => $this->photos,
            'residential_complex_name' => $this->residential_complex->name,
            'organization_name' => $this->residential_complex->organization->name,
            'room_number' => $this->room_numbber,
            'flat_number' => $this->flat,
            'price' => $this->price,
            'is_favourite' => 0,
            'rate' => $this->rate,
            'rateCount' => $this->feedbacks->count(),
            'description' => $this->description,
            'living_conditions' => LivingConditionResource::collection($this->living_conditions),
            'feedbacks' => ApartmentFeedbackResource::collection($this->feedbacks),
        ];
    }
}
