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

        if ($schema->hasColumn('requisitions', 'quote_waiver_reason')) {
            return;
        }

        $schema->table('requisitions', function (Blueprint $table) {
            $table->text('quote_waiver_reason')->nullable()->after('requires_downpayment');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        if (!$schema->hasColumn('requisitions', 'quote_waiver_reason')) {
            return;
        }

        $schema->table('requisitions', function (Blueprint $table) {
            $table->dropColumn('quote_waiver_reason');
        });
    }
};
