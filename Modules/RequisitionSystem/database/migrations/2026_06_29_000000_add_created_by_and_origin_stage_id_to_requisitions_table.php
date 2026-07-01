<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('requisitions') && !$schema->hasColumn('requisitions', 'created_by')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->uuid('created_by')->nullable()->after('id');
                $table->foreign('created_by')
                    ->references('id')
                    ->on('public.users')
                    ->onDelete('set null');
            });
        }

        if ($schema->hasTable('requisitions') && !$schema->hasColumn('requisitions', 'origin_stage_id')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->foreignId('origin_stage_id')
                    ->nullable()
                    ->after('stage_id')
                    ->constrained('stages')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('requisitions') && $schema->hasColumn('requisitions', 'origin_stage_id')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->dropForeign(['origin_stage_id']);
                $table->dropColumn('origin_stage_id');
            });
        }

        if ($schema->hasTable('requisitions') && $schema->hasColumn('requisitions', 'created_by')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
