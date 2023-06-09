<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApartmentFeedback extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $table = 'apartment_feedbacks';
    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }

}
