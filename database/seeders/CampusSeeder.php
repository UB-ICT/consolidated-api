<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CampusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // //
        // Campus::create(['id' => Str::uuid(), 'campus' => 'Business Campus',]);
        // Campus::create(['id' => Str::uuid(), 'campus' => 'FST Campus']);
        // Campus::create(['id' => Str::uuid(), 'campus' => 'IT Campus']);
        // Campus::create(['id' => Str::uuid(), 'campus' => 'Social Studies Campus']);

        $campuses = [
            [
                'id' => Str::uuid(),
                'campus' => 'Business Campus'
            ],
            [
                'id' => Str::uuid(),
                'campus' => 'FST Campus'
            ],
            [
                'id' => Str::uuid(),
                'campus' => 'IT Campus'
            ],
            [
                'id' => Str::uuid(),
                'campus' => 'Social Studies Campus'
            ],
        ];

    }
}
