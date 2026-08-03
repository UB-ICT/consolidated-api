<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensure suppliers.TAX remains nullable — some environments ended up with
 * NOT NULL after the TIN → TAX rename, which breaks seeders and quick-create.
 */
return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('suppliers') || !$schema->hasColumn('suppliers', 'TAX')) {
            return;
        }

        DB::connection($this->connection)->statement(
            'ALTER TABLE suppliers ALTER COLUMN "TAX" DROP NOT NULL'
        );
    }

    public function down(): void
    {
        // Intentionally left blank — restoring NOT NULL would re-break rows with null TAX.
    }
};
