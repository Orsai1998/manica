<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDebt extends Model
{
    use HasFactory;
    protected $table = 'user_debts';
    protected $guarded = [];
}