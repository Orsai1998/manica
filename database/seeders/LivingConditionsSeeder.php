<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LivingConditionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $conditions = [
            'Бесплатный Wi-Fi',
            'Кондиционер',
            'Телевизор',
            'Ванные принадлежности',
            'Мини бар'
        ];

        foreach ($conditions as $condition){
            \App\Models\LivingCondition::create([
                'name' => $condition
        ]);
        }
    }
}
