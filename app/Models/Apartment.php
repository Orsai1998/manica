<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Apartment extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'apartments';


    public function residential_complex(){
        return $this->belongsTo(ResidentialComplex::class,'residential_complex_id','id');
    }
    public function setBooked(){
        $this->is_available = 0;
        $this->save();
    }

    public function photos(){
        $directory = 'storage/apartments/'.$this->GUID.'/compressed';


        if (File::exists($directory) && File::isDirectory($directory)) {
            $files = File::files($directory);

            $imageFiles = array_filter($files, function ($file) {
                return strpos(File::mimeType($file), 'image') === 0;
            });
            $images = [];
            foreach ($imageFiles as $key => $image) {
                $images[] = [
                    "id" => $key,
                    "is_main" => $key == 0,
                    "path" => asset(('/' . str_replace('/storage/', '', Storage::url($image))))
                ];
            }
            return $images;
        }
         else {
            return "";
        }
    }

    public function living_conditions(){
        //return $this->belongsToMany(LivingCondition::class,'apartment_living_conditions','apartment_id','living_condition_id');
        return LivingCondition::all();
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
        $directory = 'storage/apartments/'.$this->GUID.'/compressed';

        if (File::exists($directory) && File::isDirectory($directory)) {
            $files = File::files($directory);

            $imageFiles = array_filter($files, function ($file) {
                return strpos(File::mimeType($file), 'image') === 0;
            });

            $firstImageFile = reset($imageFiles);

            if ($firstImageFile) {
                return asset(('/'.str_replace('/storage/', '',  Storage::url($firstImageFile))));


            } else {
              return "";
            }
        } else {
            return "";
        }
    }

    public function favorites(){
        return $this->belongsTo(FavoriteApartment::class,'id', 'apartment_id');
    }

    public function prices(){
        return $this->belongsTo(ApartmentPrice::class,'id', 'apartment_id');
    }

    public function getPriceForSpecificDate($date){
        $price =  $this->prices()->where('date', $date)->first();
        if($price){
            return $price->price;
        }
    }
}
