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
        if (Schema::connection($this->connection)->hasTable('user_cost_center')) {
            return;
        }

        Schema::connection($this->connection)->create('user_cost_center', function (Blueprint $table) {
            $table->id(); // Added a primary ID for the pivot table

            // 1. Create the user_id column as a UUID to match your users table
            $table->uuid('user_id');

            // 2. Create your cost center foreign key
            $table->foreignId('cost_center_id')->constrained('cost_centers')->onDelete('cascade');

            $table->timestamps();

            // 3. Bind the cross-connection foreign key to public.users
            $table->foreign('user_id')
                ->references('id')
                ->on('public.users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('user_cost_center');
    }
};
