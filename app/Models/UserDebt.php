<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDebt extends Model
{
    use HasFactory;
    protected $table = 'user_debts';
    protected $guarded = [];

    public function apartment(){
        return $this->hasOne(Apartment::class,'id');
    }
}
