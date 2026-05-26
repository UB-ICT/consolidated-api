<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('number');
            $table->foreignUuId('cost_center_id')->constrained('cost_centers');
            $table->foreignUuId('supplier_id')->constrained('suppliers');
            $table->timestamp('date_prepared')->useCurrent();
            $table->foreignUuId('status_id')->constrained('statuses');
            $table->foreignUuId('currency_id')->constrained('currencies');
            $table->foreignUuId('conversion_rate')->constrained('conversion_rates');
            $table->decimal('total', 15, 2);
            $table->foreignUuId('stage_id')->constrained('stages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};
