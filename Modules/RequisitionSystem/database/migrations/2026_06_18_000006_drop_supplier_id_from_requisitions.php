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

        if ($schema->hasTable('requisitions') && $schema->hasColumn('requisitions', 'supplier_id')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('requisitions') && !$schema->hasColumn('requisitions', 'supplier_id')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->constrained('suppliers');
            });
        }
    }
};
