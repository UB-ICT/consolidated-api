<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('personal_access_tokens') || !Schema::hasColumn('personal_access_tokens', 'tokenable_id')) {
            return;
        }

        $columnType = Schema::getColumnType('personal_access_tokens', 'tokenable_id');

        if (in_array($columnType, ['string', 'text'], true)) {
            return;
        }

        // Convert bigint/uuid/etc. tokenable_id to varchar so Sanctum can store polymorphic string keys.
        DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE varchar(255) USING tokenable_id::text');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left without a down cast because tokenable_id may contain UUID values.
    }
};
