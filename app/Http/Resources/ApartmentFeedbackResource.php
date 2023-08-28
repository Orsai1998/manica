<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;


class ApartmentFeedbackResource extends JsonResource
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
            'avatar' => $this->user->avatar ?? "",
            'user_name' => $this->user->name ?? "",
            'date' => Carbon::createFromDate($this->created_at)->format('Y-m-d'),
            'rate' => $this->rate ?? "",
            'feedback' => $this->feedback ?? "",
        ];
    }
}
