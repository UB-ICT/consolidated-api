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
        $this->ensureTableWithTimestamps('requisition_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('requisitions')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->boolean('is_recommended')->default(false);
            $table->decimal('quoted_total', 15, 2)->nullable();
            $table->string('quote_reference_number')->nullable();
            $table->timestamps();
            $table->unique(['requisition_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('requisition_suppliers');
    }
};
