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
            '5' => $this->getRateCountByRate('5'),
            '4' => $this->getRateCountByRate('4'),
            '3' => $this->getRateCountByRate('3'),
            '2' => $this->getRateCountByRate('2'),
            '1' => $this->getRateCountByRate('1'),
        ];
    }
}
