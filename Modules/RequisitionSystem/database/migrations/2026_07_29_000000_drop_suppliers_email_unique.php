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

        $schema->table('suppliers', function (Blueprint $table) {
            $table->dropUnique('suppliers_email_unique');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('suppliers')) {
            return;
        }

        $schema->table('suppliers', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
