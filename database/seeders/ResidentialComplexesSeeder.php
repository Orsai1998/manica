<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ResidentialComplexesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\ResidentialComplex::create([
            'name' => 'ЖК “4YOU”',
            'organization_id' => 1
        ]);
    }
}
