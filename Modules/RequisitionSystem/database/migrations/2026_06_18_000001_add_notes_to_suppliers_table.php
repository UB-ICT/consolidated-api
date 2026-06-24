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

        if (!$schema->hasTable('suppliers')) {
            return;
        }

        if ($schema->hasColumn('suppliers', 'notes')) {
            return;
        }

        $schema->table('suppliers', function (Blueprint $table) {
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('suppliers')) {
            return;
        }

        if (!$schema->hasColumn('suppliers', 'notes')) {
            return;
        }

        $schema->table('suppliers', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
