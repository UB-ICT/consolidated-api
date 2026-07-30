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

        if (!$schema->hasTable('suppliers') || !$schema->hasColumn('suppliers', 'TIN')) {
            return;
        }

        $schema->table('suppliers', function (Blueprint $table) {
            $table->renameColumn('TIN', 'TAX');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('suppliers') || !$schema->hasColumn('suppliers', 'TAX')) {
            return;
        }

        $schema->table('suppliers', function (Blueprint $table) {
            $table->renameColumn('TAX', 'TIN');
        });
    }
};
