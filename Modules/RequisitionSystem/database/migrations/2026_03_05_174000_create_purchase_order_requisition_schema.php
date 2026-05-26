<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::connection($this->connection)
            ->statement('CREATE SCHEMA IF NOT EXISTS purchase_order_requisition');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection($this->connection)
            ->statement('DROP SCHEMA IF EXISTS purchase_order_requisition CASCADE');
    }
};
