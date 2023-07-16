<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class UserResource extends JsonResource
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
            'avatar' =>  asset('storage/'.$this->avatar),
            'name' => $this->name,
            'isFemale' => $this->isFemale,
            'birth_date' => $this->birth_date,
            'documents' => UserDocumentsResource::collection($this->documents),
            'cards' => UserPaymentCardResource::collection($this->payment_cards)
        ];
    }

}
