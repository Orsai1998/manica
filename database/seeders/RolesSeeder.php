<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Role::create([
            'name' => 'client',
            'display_name' =>'Клиент',
        ]);
        \App\Models\Role::create([
            'name' => 'manager',
            'display_name' =>'Менеджер',
        ]);
        \App\Models\Role::create([
            'name' => 'admin',
            'display_name' =>'Админ',
        ]);
    }
}
