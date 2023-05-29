<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OrganizationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Organization::create([
            'name' => 'Manica',
            'city_id' => 1
        ]);
    }
}
