<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('last_text')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->enum('category', ['all', 'emergency', 'anonymous'])->default('all');
            $table->string('role')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('category');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('chats');
    }
};