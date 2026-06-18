<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\RequisitionSystem\Database\Migrations\EnsuresTimestamps;

return new class extends Migration
{
    use EnsuresTimestamps;

    protected $connection = 'porsql';

    public function up(): void
    {
        $this->ensureTableWithTimestamps('stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('stages') && $schema->hasColumn('stages', 'pipeline_id')) {
            $schema->table('stages', function (Blueprint $table) {
                $table->dropForeign(['pipeline_id']);
                $table->dropColumn('pipeline_id');
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('stages');
    }
};
