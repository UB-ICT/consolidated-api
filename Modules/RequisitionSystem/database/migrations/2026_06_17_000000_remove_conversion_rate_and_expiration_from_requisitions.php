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

        if ($schema->hasColumn('requisitions', 'conversion_rate_id')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->dropForeign(['conversion_rate_id']);
                $table->dropColumn('conversion_rate_id');
            });
        }

        if ($schema->hasColumn('requisitions', 'expiration_date')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->dropColumn('expiration_date');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        if (!$schema->hasColumn('requisitions', 'conversion_rate_id')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->foreignId('conversion_rate_id')
                    ->nullable()
                    ->constrained('conversion_rates');
            });
        }

        if (!$schema->hasColumn('requisitions', 'expiration_date')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->date('expiration_date')->nullable();
            });
        }
    }
};
