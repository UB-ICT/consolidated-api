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
        User::create([
            'name' => 'James',
            'email' => 'jaafaber@gmail.com',
            'picture' => './path/to/pic/',
            'domain' => 'ub.edu.bz',
            'password' => Hash::make('password'), // Hash the password using Bcrypt
            'role_id' => 1,
            'campus_id' => 1,
            'user_status_id' => 1,
        ]);

        User::create([
            'name' => 'David',
            'email' => 'jaaf@gmail.com',
            'picture' => './path/to/pic/',
            'password' => Hash::make('password'), // Hash the password using Bcrypt
            'role_id' => 2,
            'campus_id' => 2,
            'domain' => 'ub.edu.bz',
            'user_status_id' => 3,
        ]);

        User::create([
            'name' => 'Andrew',
            'email' => 'jaber@gmail.com',
            'picture' => './path/to/pic/',
            'password' => Hash::make('password'), // Hash the password using Bcrypt
            'role_id' => 3,
            'campus_id' => 3,
            'domain' => 'ub.edu.bz',

            'user_status_id' => 2,
        ]);

        User::create([
            'name' => 'Beverly',
            'email' => 'jfabe@gmail.com',
            'domain' => 'ub.edu.bz',

            'picture' => './path/to/pic/',
            'password' => Hash::make('password'), // Hash the password using Bcrypt
            'role_id' => 4,
            'campus_id' => 3,
            'user_status_id' => 2,
        ]);
    }
}