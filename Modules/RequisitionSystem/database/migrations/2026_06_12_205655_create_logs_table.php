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
            $table->id(); // integer primary key

            // Matching types perfectly to your schema definitions
            $table->uuid('requisition_id');
            $table->foreignId('stage_id')->constrained('stages')->onDelete('cascade');
            $table->uuid('user_id');

            $table->string('comments')->nullable();
            $table->timestamps(); // Keeps track of when log events occur

            // Foreign key constraints referencing target structures
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
