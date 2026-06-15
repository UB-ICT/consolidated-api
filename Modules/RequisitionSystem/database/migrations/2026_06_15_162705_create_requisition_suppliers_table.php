<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('porsql')->create('requisition_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('requisitions')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');

            // 🔥 The Magic Flag: Marks which supplier is the recommended one
            $table->boolean('is_recommended')->default(false);

            // Optional: Add tracking for quotes/pricing per vendor on this specific request
            $table->decimal('quoted_total', 15, 2)->nullable();
            $table->string('quote_reference_number')->nullable();

            $table->timestamps();

            // Safety: Ensures you don't accidentally attach the exact same vendor twice to one requisition
            $table->unique(['requisition_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisition_suppliers');
    }
};
