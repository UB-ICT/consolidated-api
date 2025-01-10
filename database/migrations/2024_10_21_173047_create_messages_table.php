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
            $table->string('user')->nullable(); // Optional sender's name
            $table->foreignId('message_category_id')->nullable()->constrained('message_categories'); // Reference message_categories table
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete(); // Reference users table with cascading null on delete
            $table->string('sender')->nullable();
            $table->string('topic')->nullable();
            $table->string('images')->nullable(); // Keep it string for now
            $table->text('text')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('date_sent')->nullable();
            $table->boolean('is_archive')->default(false); // Default to false
            $table->boolean('is_deleted')->default(false); // Default to false
            $table->boolean('is_forwarded')->default(false); // Default to false
            $table->enum('type', ['email', 'sms', 'notification'])->nullable();
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
