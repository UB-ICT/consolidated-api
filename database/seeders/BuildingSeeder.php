<?php

namespace Database\Seeders;

use App\Models\Building;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Building::create(['name'=> 'jaguar', 'location'=>'this is location', 'campus_id'=> 1]);
        Building::create(['name'=> 'Scarlet Macaw', 'location'=>'this is location', 'campus_id'=> 2]);
        Building::create(['name'=> 'Jabaroo', 'location'=>'this is location', 'campus_id'=> 3]);
        Building::create(['name'=> 'kinkajou', 'location'=>'this is location', 'campus_id'=> 2]);

    }
}
