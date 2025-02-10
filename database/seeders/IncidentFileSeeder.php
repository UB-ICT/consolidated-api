<?php

namespace Database\Seeders;

use App\Models\IncidentFile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Message\Models\Message;

class IncidentFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $messsageId = Message::all()->pluck('id')->toArray();

        IncidentFile::create(['path' => 'path/to/file1', 'comment' => 'Comment for file 1', 'message_id' => $messsageId[0],]);
        IncidentFile::create(['path' => 'path/to/file2', 'comment' => 'Comment for file 2', 'message_id' =>$messsageId[1],]);
        IncidentFile::create(['path' => 'path/to/file3', 'comment' => 'Comment for file 3', 'message_id' => $messsageId[2],]);
        IncidentFile::create(['path' => 'path/to/file4', 'comment' => 'Comment for file 4', 'message_id' => $messsageId[2],]);
    }
}
