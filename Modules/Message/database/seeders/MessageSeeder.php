<?php

namespace Modules\Message\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Message\Models\Message;


class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Message::create([
            'user' => 'James Faber',
            'message_category_id' => 1,
            'sender_id' => 1,
            'sender' => 'own',
            'topic' => 'Topic 1',
            'images' => 'image1.jpg',
            'message' => 'This is message 1 content.',
            'location' => 'Location 1',
            'date_sent' => now(),
            'is_archive' => false,
            'is_deleted' => false,
            'is_forwarded' => false,
            'type' => 'email',
        ]);

        Message::create([
            'user' => 'Shanell Leslie',
            'message_category_id' => 2,
            'sender_id' => 1,
            'sender' => 'own',
            'topic' => 'Topic 1',
            'images' => 'image1.jpg',
            'message' => 'This is message 1 content.',
            'location' => 'Location 2',
            'date_sent' => now(),
            'is_archive' => false,
            'is_deleted' => false,
            'is_forwarded' => false,
            'type' => 'email',
        ]);

        Message::create([
            'user' => 'Steve Castillo',
            'message_category_id' => 3,
            'sender_id' => 3,
            'sender' => 'own',
            'topic' => 'Topic 3',
            'images' => 'image3.jpg',
            'message' => 'This is message 3 content.',
            'location' => 'Location 3',
            'date_sent' => now(),
            'is_archive' => false,
            'is_deleted' => false,
            'is_forwarded' => false,
            'type' => 'sms',
        ]);

        Message::create([
            'user' => 'Daren Brown',
            'message_category_id' => 4,
            'sender_id' => 4,
            'sender' => 'own',
            'topic' => 'Message 4',
            'images' => 'image4.jpg',
            'message' => 'This is message 4 content.',
            'location' => 'Location 4',
            'date_sent' => now(),
            'is_archive' => false,
            'is_deleted' => false,
            'is_forwarded' => false,
            'type' => 'notification',
        ]);
    }
}
