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
            // $table->id();
            // $table->string('user')->nullable(); // Optional sender's name
            // $table->foreignId('message_category_id')->nullable()->constrained('message_categories')->onDelete('cascade'); // Reference message_categories table
            // $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete(); // Reference users table with cascading null on delete
            // $table->string('sender')->nullable();
            // $table->string('topic')->nullable();
            // $table->string('images')->nullable(); // Keep it string for now
            // $table->text('message')->nullable();
            // $table->string('location')->nullable();
            // $table->dateTime('date_sent')->nullable();
            // $table->boolean('is_archive')->default(false); // Default to false
            // $table->boolean('is_deleted')->default(false); // Default to false
            // $table->boolean('is_forwarded')->default(false); // Default to false 
            // $table->enum('type', ['email', 'sms', 'notification'])->nullable();

            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete(); // Add chat reference
            $table->foreignId('sender_id')->constrained('users')->nullOnDelete();
            $table->text('content'); // Renamed from 'message' for consistency
            $table->json('attachments')->nullable(); // Better than string for images/files
            $table->timestamp('read_at')->nullable(); // For read receipts
            $table->boolean('is_forwarded')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->enum('type', ['text', 'image', 'video', 'document', 'audio'])->default('text');
            $table->timestamps();
        });

      
        Schema::create('chat_user', function (Blueprint $table) {
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['chat_id', 'user_id']);
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
