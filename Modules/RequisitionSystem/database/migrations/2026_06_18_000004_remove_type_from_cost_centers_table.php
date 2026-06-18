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

        if (!$schema->hasTable('cost_centers')) {
            return;
        }

        if (!$schema->hasColumn('cost_centers', 'type')) {
            return;
        }

        $schema->table('cost_centers', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('cost_centers')) {
            return;
        }

        if ($schema->hasColumn('cost_centers', 'type')) {
            return;
        }

        $schema->table('cost_centers', function (Blueprint $table) {
            $table->string('type')->nullable();
        });
    }
};
