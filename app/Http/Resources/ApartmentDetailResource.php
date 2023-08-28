<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentDetailResource extends JsonResource
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
            'photos' => $this->photos(),
            'area' => $this->area,
            'residential_complex_name' => $this->residential_complex->name ?? "",
            'organization_name' => $this->residential_complex->organization->name ?? "",
            'room_number' => $this->room_number,
            'flat_number' => $this->flat,
            'floor' => $this->floor,
            'depozit' => 20000,
            'block' => $this->block,
            'entrance' => $this->entrance,
            'price' => $this->prices->price,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'is_favourite' => 0,
            'rate' => $this->rate(),
            'feedbackCount' => $this->feedbacks->count(),
            'description' => $this->description,
            'living_conditions' => LivingConditionResource::collection($this->living_conditions),
            'feedbacks' => ApartmentFeedbackResource::collection($this->feedbacks),
        ];
    }
}
