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
            'username' => 'Kingjames',
            'email' => 'jaafaber@gmail.com',
            'phone_no' => '605-2234',
            'organization' => 'University of Belize',
            'picture' => './path/to/pic/',
            'domain' => 'ub.edu.bz',
            'password' => Hash::make('password'), // Hash the password using Bcrypt
            'role_id' => 1,
            'campus_id' => 1,
            'user_status_id' => 1,
        ]);

        User::create([
            'name' => 'David',
            'username' => 'Kingjames11',
            'email' => 'jaaf@gmail.com',
            'phone_no' => '605-5331',
            'organization' => 'University of Belize',
            'picture' => './path/to/pic/',
            'domain' => 'ub.edu.bz',
            'password' => Hash::make('password'), // Hash the password using Bcrypt
            'role_id' => 2,
            'campus_id' => 2,
            'user_status_id' => 3,
        ]);

        User::create([
            'name' => 'Andrew',
            'username' => 'Kingjames111',
            'email' => 'jaber@gmail.com',
            'phone_no' => '622-2234',
            'organization' => 'University of Belize',
            'picture' => './path/to/pic/',
            'domain' => 'ub.edu.bz',
            'password' => Hash::make('password'), // Hash the password using Bcrypt
            'role_id' => 3,
            'campus_id' => 3,
            'user_status_id' => 2,
        ]);

        User::create([
            'name' => 'Beverly',
            'username' => 'Kingjames1111',
            'email' => 'jfabe@gmail.com',
            'phone_no' => '622-0234',
            'organization' => 'University of Belize',
            'picture' => './path/to/pic/',
            'domain' => 'ub.edu.bz',
            'password' => Hash::make('password'), // Hash the password using Bcrypt
            'role_id' => 4,
            'campus_id' => 3,
            'user_status_id' => 2,
        ]);
    }
}