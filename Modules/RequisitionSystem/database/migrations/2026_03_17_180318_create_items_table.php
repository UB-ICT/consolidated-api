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
        Schema::create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('description');
            $table->integer('quantity');
            $table->integer('line_item_number');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('comments')->nullable();
            $table->foreignUuId('requisition_id')->constrained('requisitions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
