<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserDebtsResource extends JsonResource
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
            'id' => $this->id,
            'apartment' =>  new ApartmentResource($this->apartment),
            'paymentType' => $this->paymentType,
            'balance' => $this->balance,
            'need_to_pay' => false,
        ];
    }
}
