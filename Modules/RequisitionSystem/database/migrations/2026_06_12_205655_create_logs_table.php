<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('logs', function (Blueprint $table) {
            $table->id();

            // 1. Changed from uuid to unsignedBigInteger to perfectly match requisitions.id
            $table->unsignedBigInteger('requisition_id');

            $table->foreignId('stage_id')->constrained('stages')->onDelete('cascade');

            // 2. Keep this as uuid ONLY if your public.users table uses UUIDs as its primary key!
            $table->uuid('user_id');

            $table->string('comments')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('requisition_id')
                ->references('id')
                ->on('requisitions')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('public.users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('logs');
    }
};
