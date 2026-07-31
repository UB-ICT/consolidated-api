<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Legacy migration: line_item_number was removed from items in favor of
 * chart_of_account_id. Kept as a no-op for migration history.
 */
return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        // no-op
    }

    public function down(): void
    {
        // no-op
    }
};
