<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'porsql';

    public function up(): void
    {
        Schema::connection($this->connection)->table('requisitions', function (Blueprint $table) {
            // Adds the column as an integer, defaulting to 1 
            $table->integer('current_stage_sequence')
                ->default(1)
                ->after('stage_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('requisitions', function (Blueprint $table) {
            $table->dropColumn('current_stage_sequence');
        });
    }
};