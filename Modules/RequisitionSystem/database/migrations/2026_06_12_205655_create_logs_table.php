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
            $table->id(); // integer primary key (bigint)

            // 🔥 FIX: Changed from uuid() to foreignId() to match your requisitions id
            $table->foreignId('requisition_id')->constrained('requisitions')->onDelete('cascade');
            $table->foreignId('stage_id')->constrained('stages')->onDelete('cascade');

            // Keeps it as a UUID if your app's core users table actually uses UUIDs
            $table->uuid('user_id');

            $table->string('comments')->nullable();
            $table->timestamps();

            // Foreign key constraint for your users table
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
