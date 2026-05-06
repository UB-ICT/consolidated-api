<?php

namespace Modules\PublicSafety\Database\Seeders;

use Modules\PublicSafety\Models\MessageCategory;
use Illuminate\Database\Seeder;

class MessageCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MessageCategory::create(['category' => 'Bullying']);
        MessageCategory::create(['category' => 'Fire']);
        MessageCategory::create(['category' => 'Snake']);
        MessageCategory::create(['category' => 'Fighting']);
    }
}
