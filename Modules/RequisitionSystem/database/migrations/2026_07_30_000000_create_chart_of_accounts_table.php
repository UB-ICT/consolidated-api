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

        if ($schema->hasTable('chart_of_accounts')) {
            return;
        }

        $schema->create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_no', 20)->unique();
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('chart_of_accounts');
    }
};
