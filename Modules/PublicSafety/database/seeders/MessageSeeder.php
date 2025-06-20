<?php

namespace Modules\PublicSafety\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PublicSafety\Models\Message;


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
            'updated_at' => now(),
            'created_at' => now(),
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
            'updated_at' => now(),
            'created_at' => now(),
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
            'updated_at' => now(),
            'created_at' => now(),
        ]);




    }
}
