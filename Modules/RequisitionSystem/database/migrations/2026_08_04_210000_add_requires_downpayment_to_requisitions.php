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

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        if ($schema->hasColumn('requisitions', 'requires_downpayment')) {
            return;
        }

        $schema->table('requisitions', function (Blueprint $table) {
            $table->boolean('requires_downpayment')->default(false)->after('is_recurring');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        if (!$schema->hasColumn('requisitions', 'requires_downpayment')) {
            return;
        }

        $schema->table('requisitions', function (Blueprint $table) {
            $table->dropColumn('requires_downpayment');
        });
    }
};
