<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensure supplier_banks.address remains nullable — some environments have
 * NOT NULL despite the original create migration marking it nullable.
 */
return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (
            !$schema->hasTable('supplier_banks')
            || !$schema->hasColumn('supplier_banks', 'address')
        ) {
            return;
        }

        DB::connection($this->connection)->statement(
            'ALTER TABLE supplier_banks ALTER COLUMN address DROP NOT NULL'
        );
    }

    public function down(): void
    {
        // Intentionally left blank — restoring NOT NULL would break rows with null address.
    }
};
