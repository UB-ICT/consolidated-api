<?php

namespace Modules\Message\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Message\Models\Message;
use App\Models\User;
use Modules\Message\Models\Chat;
use Modules\Message\Models\MessageFile;


class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Message::create([
            "profile_pic" => 'https://example.com/profile_pic.jpg',
            'sender' => 'Shanell Leslie',
            'message_category_id' => 1,
            'images' => 'image1.jpg',
            'message' => 'This is message 1 content.',
            'location' => 'Location 1',
            'date_sent' => now(),
            'is_deleted' => false,
            'type' => 'emergency',
        ]);

        Message::create([
            "profile_pic" => 'https://example.com/profile_pic.jpg',
            'sender' => 'Andrew Faber',
            'message_category_id' => 2,
            'images' => 'image1.jpg',
            'message' => 'This is message 2 content.',
            'location' => 'Location 2',
            'date_sent' => now(),
            'is_deleted' => false,
            'type' => 'anonymous',
        ]);

        Message::create([
            "profile_pic" => 'https://example.com/profile_pic.jpg',
            'sender' => 'David Faber',
            'message_category_id' => 3,
            'images' => 'image1.jpg',
            'message' => 'This is message 3 content.',
            'location' => 'Location 3',
            'date_sent' => now(),
            'is_deleted' => false,
            'type' => 'all',
        ]);




    }
}
