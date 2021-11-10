<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rooms;

class RoomsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Rooms::insert([
            ['id' => 1, 'quota' => 10, 'name' => 'Делюкс с одной большой кроватью'],
            ['id' => 2, 'quota' => 8, 'name' => 'Делюкс с двумя раздельными кроватями'],
            ['id' => 3, 'quota' => 10, 'name' => 'Представительский Делюкс с одной большой кроватью'],
            ['id' => 4, 'quota' => 9, 'name' => 'Представительский Делюкс с двумя кроватями'],
            ['id' => 5, 'quota' => 4, 'name' => 'Люкс'],
        ]);

    }
}




