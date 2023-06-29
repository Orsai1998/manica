<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function apartments(){
        return $this->hasOne(Apartment::class,'id','apartment_id');
    }

    public function numberOfDays(){

        $entry_date = new DateTime($this->entry_date);
        $depature_date = new DateTime($this->departure_date);
        $interval = $entry_date->diff($depature_date);
        $days = $interval->format('%a');

        return $days;
    }

    public function status(){
        if($this->depature_date >= Carbon::now()){
            return 'Активная';
        }
        return 'Не активная';
    }
}
