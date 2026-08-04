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

        $schema->table('requisitions', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('requisitions', 'purchase_order_file_name')) {
                $table->string('purchase_order_file_name')->nullable()->after('purchase_order_number');
            }

            if (!$schema->hasColumn('requisitions', 'purchase_order_file_path')) {
                $table->string('purchase_order_file_path')->nullable()->after('purchase_order_file_name');
            }

            if (!$schema->hasColumn('requisitions', 'purchase_order_emailed_at')) {
                $table->timestamp('purchase_order_emailed_at')->nullable()->after('purchase_order_file_path');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        $schema->table('requisitions', function (Blueprint $table) use ($schema) {
            foreach ([
                'purchase_order_emailed_at',
                'purchase_order_file_path',
                'purchase_order_file_name',
            ] as $column) {
                if ($schema->hasColumn('requisitions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
