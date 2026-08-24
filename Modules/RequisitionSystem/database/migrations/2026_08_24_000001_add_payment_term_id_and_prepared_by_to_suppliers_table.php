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

        if (!$schema->hasTable('suppliers')) {
            return;
        }

        $schema->table('suppliers', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('suppliers', 'payment_term_id')) {
                $table->foreignId('payment_term_id')
                    ->nullable()
                    ->after('status_id')
                    ->constrained('payment_terms')
                    ->onDelete('set null');
            }

            if (!$schema->hasColumn('suppliers', 'prepared_by')) {
                $table->string('prepared_by')->nullable()->after('payment_term_id');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('suppliers')) {
            return;
        }

        $schema->table('suppliers', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('suppliers', 'payment_term_id')) {
                $table->dropConstrainedForeignId('payment_term_id');
            }

            if ($schema->hasColumn('suppliers', 'prepared_by')) {
                $table->dropColumn('prepared_by');
            }
        });
    }
};
