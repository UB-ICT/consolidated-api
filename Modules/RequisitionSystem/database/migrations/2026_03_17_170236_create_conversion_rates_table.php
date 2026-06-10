<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('conversion_rates')) {
            return;
        }

        Schema::connection($this->connection)->create('conversion_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('rate', 10, 2);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('conversion_rates');
    }
};
