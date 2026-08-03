<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widen supplier_banks.account_number — some vendor sheet "account" values
 * include branch/address text after the bank code and exceed VARCHAR(50).
 */
return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (
            !$schema->hasTable('supplier_banks')
            || !$schema->hasColumn('supplier_banks', 'account_number')
        ) {
            return;
        }

        DB::connection($this->connection)->statement(
            'ALTER TABLE supplier_banks ALTER COLUMN account_number TYPE VARCHAR(255)'
        );
    }

    public function down(): void
    {
        // Not reversible — existing values may already exceed 50 characters.
    }
};
