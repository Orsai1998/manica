<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentialComplex extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function organization(){

        return $this->belongsTo(Organization::class,'organization_id','id');
    }
}
