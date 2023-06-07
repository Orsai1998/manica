<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPaymentCard extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'user_payment_cards';
}
