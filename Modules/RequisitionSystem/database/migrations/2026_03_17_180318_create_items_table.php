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
        $this->ensureTableWithTimestamps('items', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->integer('quantity');
            $table->string('line_item_number', 50);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('comments')->nullable();
            $table->foreignId('requisition_id')->constrained('requisitions')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('items');
    }
};
