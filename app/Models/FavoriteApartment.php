<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteApartment extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'user_favourite_apartments';
}
