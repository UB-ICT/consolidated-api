<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The supplier form now only captures a single free-text address line
 * (Address.street), so city/district/country_id can no longer be assumed
 * to be filled in alongside it.
 */
return new class extends Migration
{
    protected $connection = 'porsql';

    private const COLUMNS = ['city', 'district', 'country_id'];

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('addresses')) {
            return;
        }

        foreach (self::COLUMNS as $column) {
            if (!$schema->hasColumn('addresses', $column)) {
                continue;
            }

            DB::connection($this->connection)->statement(
                "ALTER TABLE addresses ALTER COLUMN {$column} DROP NOT NULL"
            );
        }
    }

    public function down(): void
    {
        // Intentionally left blank — restoring NOT NULL would break rows
        // saved with only a street value.
    }
};
