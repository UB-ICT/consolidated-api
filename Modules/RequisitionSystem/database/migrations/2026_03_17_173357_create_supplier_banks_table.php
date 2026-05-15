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
        Schema::create('supplier_banks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuId('supplier_id')->constrained('suppliers');
            $table->foreignUuId('bank_id')->constrained('banks');
            $table->integer('account_number');
            $table->string('account_name');
            $table->string('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_banks');
    }
};
