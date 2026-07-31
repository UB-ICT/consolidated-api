<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops free-text line item fields once chart_of_account_id is the source of truth.
 * Safe to re-run after the updated 2026_07_30_000001 migration (no-op if already dropped).
 */
return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('items')) {
            return;
        }

        foreach (['line_item_number', 'description'] as $column) {
            if ($schema->hasColumn('items', $column)) {
                $schema->table('items', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('items')) {
            return;
        }

        if (!$schema->hasColumn('items', 'description')) {
            $schema->table('items', function (Blueprint $table) {
                $table->string('description')->nullable();
            });
        }

        if (!$schema->hasColumn('items', 'line_item_number')) {
            $schema->table('items', function (Blueprint $table) {
                $table->string('line_item_number', 50)->nullable();
            });
        }
    }
};
