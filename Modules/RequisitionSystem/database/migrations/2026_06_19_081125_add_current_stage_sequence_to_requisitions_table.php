<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        // Check if the table exists AND the column DOES NOT exist yet
        if ($schema->hasTable('requisitions') && !$schema->hasColumn('requisitions', 'current_stage_sequence')) {
            $schema->table('requisitions', function (Blueprint $table) {
                // Adds the column as an integer, defaulting to 1 
                $table->integer('current_stage_sequence')
                    ->default(1)
                    ->after('stage_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('requisitions') && $schema->hasColumn('requisitions', 'current_stage_sequence')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->dropColumn('current_stage_sequence');
            });
        }
    }
};