<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ApartmentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\ApartmentType::create([
            'name' => 'Квартиры'
        ]);
        \App\Models\ApartmentType::create([
            'name' => 'Пентхаусы'
        ]);
    }
}
