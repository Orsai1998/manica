<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDocument extends Model
{
    protected $fillable = [
        'user_id',
        'path',
        'name',
        'isFrontSide'
    ];
    use HasFactory,SoftDeletes;
}
