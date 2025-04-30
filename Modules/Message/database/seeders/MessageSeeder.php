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
        //
        // Message::create([
        //     'user' => 'James Faber',
        //     'message_category_id' => 1,
        //     'sender_id' => 1,
        //     'sender' => 'own',
        //     'topic' => 'Topic 1',
        //     'images' => 'image1.jpg',
        //     'message' => 'This is message 1 content.',
        //     'location' => 'Location 1',
        //     'date_sent' => now(),
        //     'is_archive' => false,
        //     'is_deleted' => false,
        //     'is_forwarded' => false,
        //     'type' => 'email',
        // ]);

        $chats = Chat::all();
        $users = User::all();

        $sampleMessages = [
            "Hello there!",
            "How are you doing?",
            "What's up?",
            "Let's meet tomorrow",
            "Did you see that?",
            "I need your help",
            "Check this out",
            "Important information",
            "Emergency situation",
            "Anonymous tip"
        ];

        $sampleImages = [
            'https://picsum.photos/300/300?random=1',
            'https://picsum.photos/300/300?random=2',
            'https://picsum.photos/300/300?random=3',
            'https://picsum.photos/300/300?random=4',
            'https://picsum.photos/300/300?random=5',
        ];

        foreach ($chats as $chat) {
            // Create 5-15 messages per chat
            $messageCount = rand(5, 15);

            for ($i = 0; $i < $messageCount; $i++) {
                $user = $users->random();
                $hasImage = rand(0, 1);

                $message = Message::create([
                    'chat_id' => $chat->id,
                    'sender_id' => $user->id,
                    'text' => $sampleMessages[array_rand($sampleMessages)],
                    'timestamp' => now()->subDays(rand(0, 30))->subHours(rand(0, 24)),
                ]);

                // 30% chance to add an image to the message
                if ($hasImage && $i % 3 === 0) {
                    MessageFile::create([
                        'message_id' => $message->id,
                        'url' => $sampleImages[array_rand($sampleImages)],
                        'name' => 'image_' . $i . '.jpg',
                        'type' => 'image',
                    ]);
                }
            }

            // Update chat's last message
            $lastMessage = $chat->messages()->latest('timestamp')->first();
            if ($lastMessage) {
                $chat->update([
                    'last_text' => $lastMessage->text
                ]);
            }
        }

    }
}
