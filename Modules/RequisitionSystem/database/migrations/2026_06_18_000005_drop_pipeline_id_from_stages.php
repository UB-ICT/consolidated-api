<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('stages') && $schema->hasColumn('stages', 'pipeline_id')) {
            $schema->table('stages', function (Blueprint $table) {
                $table->dropForeign(['pipeline_id']);
                $table->dropColumn('pipeline_id');
            });
        }
    }

    public function down(): void
    {
        // Dropping pipeline_id during refresh rollback would recreate a FK that
        // blocks dropping pipelines before stages is removed.
    }
};
