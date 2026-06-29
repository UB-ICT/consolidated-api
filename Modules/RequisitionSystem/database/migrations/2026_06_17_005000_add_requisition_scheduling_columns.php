<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        $schema->table('requisitions', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('requisitions', 'priority')) {
                $table->string('priority')->default('routine');
            }

            if (!$schema->hasColumn('requisitions', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable();
            }

            if (!$schema->hasColumn('requisitions', 'is_recurring')) {
                $table->boolean('is_recurring')->default(false);
            }

            if (!$schema->hasColumn('requisitions', 'reminder_date')) {
                $table->date('reminder_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        $schema->table('requisitions', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('requisitions', 'reminder_date')) {
                $table->dropColumn('reminder_date');
            }

            if ($schema->hasColumn('requisitions', 'is_recurring')) {
                $table->dropColumn('is_recurring');
            }

            if ($schema->hasColumn('requisitions', 'expected_delivery_date')) {
                $table->dropColumn('expected_delivery_date');
            }

            if ($schema->hasColumn('requisitions', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
