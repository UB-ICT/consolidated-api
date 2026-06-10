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
        if (Schema::connection($this->connection)->hasTable('approvals')) {
            return;
        }

        Schema::connection($this->connection)->create('approvals', function (Blueprint $table) {
            $table->id();
            // 1. Create the user_id column as a UUID to match your users table
            $table->uuid('user_id');

            $table->foreignId('requisition_id')->constrained('requisitions');
            $table->timestamp('signed_at')->useCurrent();
            $table->string('comments')->nullable();
            // 3. Bind the cross-connection foreign key to public.users
            $table->foreign('user_id')
                ->references('id')
                ->on('public.users')
                ->onDelete('cascade');
            $table->timestamps();
            $table->foreignId('stage_id')->nullable()->constrained('stages');
            $table->string('status')->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('approvals');
    }
};
