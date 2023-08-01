<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'payments';
    protected $guarded = [];


    public function setToken($token){

        $this->payment_token = $token;
        $this->save();
    }

    public function setSuccessStatus(){

        $this->status = 'PAID';
        $this->save();
    }

    public function payment_method(){

        return $this->hasOne(UserPaymentCard::class,"id","user_card_id");
    }
}
