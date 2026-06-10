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
        if (Schema::connection($this->connection)->hasTable('requisitions')) {
            return;
        }

        Schema::connection($this->connection)->create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->timestamp('date_prepared')->useCurrent();
            $table->foreignId('status_id')->constrained('statuses');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('conversion_rate_id')->constrained('conversion_rates');
            $table->decimal('total', 15, 2);
            $table->foreignId('stage_id')->constrained('stages');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('requisitions');
    }
};
