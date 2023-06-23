<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Apartment extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'apartments';


    public function residential_complex(){
        return $this->belongsTo(ResidentialComplex::class,'residential_complex_id','id');
    }

    public function photos() : HasMany{
        return $this->hasMany(ApartmentPhoto::class,'apartment_id');
    }

    public function living_conditions(){
        return $this->belongsToMany(LivingCondition::class,'apartment_living_conditions','apartment_id','living_condition_id');
    }

    public function bookings(){
        return $this->belongsTo(Booking::class,'id','apartment_id');
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

    public function favorite(){

        return $this->hasMany(FavoriteApartment::class,'apartment_id');
    }

    public function is_favorite(){
        $user = Auth::user();

        if($user){
            $count = $this->favorite()->where('user_id', $user->id)->count();
            if($count > 0){
                return 1;
            }
        }
        return 0;
    }

    public function mainPhoto(){
       $photo =  $this->photos()->where('is_main', '=','1')->first();

       if($photo){
           return asset('storage/'.$photo->path);
       }
       return  "";
    }

    public function prices(){
        return $this->hasOne(ApartmentPrice::class,'apartment_id', 'id');
    }
}
