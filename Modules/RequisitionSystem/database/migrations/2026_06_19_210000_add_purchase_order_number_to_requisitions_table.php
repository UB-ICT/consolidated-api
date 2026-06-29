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

        if ($schema->hasColumn('requisitions', 'purchase_order_number')) {
            return;
        }

        $schema->table('requisitions', function (Blueprint $table) {
            $table->string('purchase_order_number', 100)->nullable()->after('number');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        if ($schema->hasColumn('requisitions', 'purchase_order_number')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->dropColumn('purchase_order_number');
            });
        }
    }
};
