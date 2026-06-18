<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        if (!$schema->hasColumn('requisitions', 'priority')) {
            return;
        }

        $priorityMap = [
            'low' => 'routine',
            'medium' => 'standard',
            'high' => 'urgent',
        ];

        foreach ($priorityMap as $from => $to) {
            DB::connection($this->connection)
                ->table('requisitions')
                ->where('priority', $from)
                ->update(['priority' => $to]);
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        if (!$schema->hasColumn('requisitions', 'priority')) {
            return;
        }

        $priorityMap = [
            'routine' => 'low',
            'standard' => 'medium',
            'urgent' => 'high',
        ];

        foreach ($priorityMap as $from => $to) {
            DB::connection($this->connection)
                ->table('requisitions')
                ->where('priority', $from)
                ->update(['priority' => $to]);
        }
    }
};
