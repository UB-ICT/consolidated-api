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
        if (Schema::connection($this->connection)->hasTable('items')) {
            return;
        }

        Schema::connection($this->connection)->create('items', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->integer('quantity');
            $table->integer('line_item_number');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('comments')->nullable();
            $table->foreignId('requisition_id')->constrained('requisitions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('items');
    }
};
