<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\RequisitionSystem\Database\Migrations\EnsuresTimestamps;

return new class extends Migration
{
    use EnsuresTimestamps;

    protected $connection = 'porsql';

    public function up(): void
    {
        $this->ensureTableWithTimestamps('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->dropIfExists('user_cost_center');
        $schema->dropIfExists('por_users');

        DB::connection($this->connection)->statement(
            'DROP TABLE IF EXISTS cost_centers CASCADE'
        );
    }
};
