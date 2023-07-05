<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentDetailedFeedbackResource extends JsonResource
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
            'five' => $this->getRateCountByRate('5'),
            'four' => $this->getRateCountByRate('4'),
            'three' => $this->getRateCountByRate('3'),
            'two' => $this->getRateCountByRate('2'),
            'one' => $this->getRateCountByRate('1'),
        ];
    }
}
