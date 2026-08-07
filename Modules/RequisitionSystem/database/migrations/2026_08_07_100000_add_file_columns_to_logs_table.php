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

        if (!$schema->hasTable('logs')) {
            return;
        }

        $schema->table('logs', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('logs', 'file_name')) {
                $table->string('file_name')->nullable()->after('comments');
            }

            if (!$schema->hasColumn('logs', 'file_path')) {
                $table->string('file_path')->nullable()->after('file_name');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('logs')) {
            return;
        }

        $schema->table('logs', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('logs', 'file_path')) {
                $table->dropColumn('file_path');
            }

            if ($schema->hasColumn('logs', 'file_name')) {
                $table->dropColumn('file_name');
            }
        });
    }
};
