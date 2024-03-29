<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
class UserDocumentsResource  extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'path' =>   asset('storage/'.$this->path),
        ];
    }
}
