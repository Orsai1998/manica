<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    use HasFactory;



    public function residential_complex(){
        return $this->belongsTo(ResidentialComplex::class,'residential_complex_id','id');
    }

    public function photos() : HasMany{
        return $this->hasMany(ApartmentPhoto::class);
    }

    public function living_conditions(){
        return $this->belongsToMany(LivingCondition::class,'apartment_living_conditions','apartment_id','living_condition_id');
    }

    public function feedbacks(){
        return $this->hasMany(ApartmentFeedback::class);
    }

    public function getRateCountByRate($rate){
        return $this->feedbacks()->where('rate', $rate)->count();
    }

    public function rate(){
        return $this->feedbacks()->average('rate');
    }

    public function mainPhoto(){
       $photo =  $this->photos()->where('is_main', '=','1')->first();

       if($photo){
           return $photo->path;
       }
       return  "";
    }

    public function price(){
        return 1000;
    }
}
