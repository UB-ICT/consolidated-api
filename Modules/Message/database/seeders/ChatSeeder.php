<?php

namespace Modules\Message\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Message\Models\Chat;
use App\Models\User;


class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $users = User::all();
        
        // Create some individual chats
        foreach ($users as $user) {
            Chat::create([
                'name' => 'Chat with ' . $user->name,
                'last_text' => 'Hello there!',
                'category' => ['all', 'emergency', 'anonymous'][rand(0, 2)],
                'role' => ['user', 'admin', 'support'][rand(0, 2)],
                'avatar_url' => 'https://i.pravatar.cc/150?img=' . rand(1, 70),
            ]);
        }

        // Create some group chats
        for ($i = 0; $i < 5; $i++) {
            Chat::create([
                'name' => 'Group Chat ' . ($i + 1),
                'last_text' => 'Welcome to the group!',
                'category' => 'all',
                'role' => 'group',
                'avatar_url' => 'https://i.pravatar.cc/150?img=' . rand(1, 70),
            ]);
        }
    }
}
