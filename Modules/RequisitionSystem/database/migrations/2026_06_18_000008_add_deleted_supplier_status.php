<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\RequisitionSystem\Support\SupplierStatus;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        if (!Schema::connection($this->connection)->hasTable('statuses')) {
            return;
        }

        DB::connection($this->connection)->table('statuses')->updateOrInsert(
            ['id' => SupplierStatus::DELETED_ID],
            ['name' => SupplierStatus::DELETED]
        );
    }

    public function down(): void
    {
        if (!Schema::connection($this->connection)->hasTable('statuses')) {
            return;
        }

        DB::connection($this->connection)
            ->table('statuses')
            ->where('id', SupplierStatus::DELETED_ID)
            ->delete();
    }
};
