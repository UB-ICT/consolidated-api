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
    }
}
