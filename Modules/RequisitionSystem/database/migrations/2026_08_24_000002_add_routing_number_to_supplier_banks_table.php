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

        if (!$schema->hasTable('supplier_banks') || $schema->hasColumn('supplier_banks', 'routing_number')) {
            return;
        }

        $schema->table('supplier_banks', function (Blueprint $table) {
            $table->string('routing_number')->nullable()->after('account_name');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('supplier_banks') || !$schema->hasColumn('supplier_banks', 'routing_number')) {
            return;
        }

        $schema->table('supplier_banks', function (Blueprint $table) {
            $table->dropColumn('routing_number');
        });
    }
};
