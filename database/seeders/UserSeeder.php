<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'name' => 'James Faber',
            'email' => 'jamess.faber@ub.edu.bz',
            'domain' => 'default',
            'password' => Hash::make('Kingjames_x2'), // Hash the password using Bcrypt
            'role_id' => 1,
            'campus_id' => 2,
            'user_status_id' => 3,
        ]);

       
      

     
    }
}