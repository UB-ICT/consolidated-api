<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    /**
     * Add cost_center_id FK to the shared users table after cost_centers is created.
     */
    public function up(): void
    {
        $connection = DB::connection($this->connection);
        $schema = $connection->getConfig('schema') ?? 'public';

        $columnType = $connection->table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', 'users')
            ->where('column_name', 'cost_center_id')
            ->value('data_type');

        if ($columnType && $columnType !== 'uuid') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('cost_center_id');
            });

            $columnType = null;
        }

        if (!$columnType) {
            Schema::table('users', function (Blueprint $table) {
                $table->uuid('cost_center_id')->nullable();
            });
        }

        $hasForeignKey = $connection->table('information_schema.table_constraints')
            ->where('table_schema', $schema)
            ->where('table_name', 'users')
            ->where('constraint_name', 'users_cost_center_id_foreign')
            ->exists();

        if (!$hasForeignKey) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('cost_center_id')
                    ->references('id')
                    ->on('cost_centers')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cost_center_id']);
        });
    }
};
