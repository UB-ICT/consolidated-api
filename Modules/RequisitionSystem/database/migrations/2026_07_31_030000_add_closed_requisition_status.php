<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        DB::connection($this->connection)->table('statuses')->updateOrInsert(
            ['id' => 9],
            ['name' => 'Closed']
        );
    }

    public function down(): void
    {
        DB::connection($this->connection)->table('statuses')
            ->where('id', 9)
            ->where('name', 'Closed')
            ->delete();
    }
};
