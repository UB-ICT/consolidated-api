<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('profile_pic')->nullable(); //senders profile picture
            $table->string(column: 'sender')->nullable();
            $table->foreignId('message_category_id')->nullable()->constrained('message_categories')->onDelete('cascade'); // Reference message_categories table
            $table->string('images')->nullable(); // Keep it string for now
            $table->text('message')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('date_sent')->nullable();
            $table->boolean('is_deleted')->default(false); // Default to false
            $table->enum('type', ['all', 'emergency', 'anonymous'])->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
