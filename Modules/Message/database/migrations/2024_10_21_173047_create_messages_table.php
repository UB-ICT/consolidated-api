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
        // Schema::create('messages', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('user')->nullable(); // Optional sender's name
        //     $table->foreignId('message_category_id')->nullable()->constrained('message_categories')->onDelete('cascade'); // Reference message_categories table
        //     $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete(); // Reference users table with cascading null on delete
        //     $table->string('sender')->nullable();
        //     $table->string('topic')->nullable();
        //     $table->string('images')->nullable(); // Keep it string for now
        //     $table->text('message')->nullable();
        //     $table->string('location')->nullable();
        //     $table->dateTime('date_sent')->nullable();
        //     $table->boolean('is_archive')->default(false); // Default to false
        //     $table->boolean('is_deleted')->default(false); // Default to false
        //     $table->boolean('is_forwarded')->default(false); // Default to false 
        //     $table->enum('type', ['email', 'sms', 'notification'])->nullable();
        // });

        Schema::create('messages', function (Blueprint $table) {
            $table->id(); // Standard auto-incrementing primary key (remove ->unique() as it's redundant)

            // Changed from foreignUuid to unsignedBigInteger
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('sender_id');

            $table->text('text')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('chat_id')
                ->references('id')
                ->on('chats')
                ->onDelete('cascade');

            $table->foreign('sender_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->index('chat_id');
            $table->index('sender_id');
            $table->index('timestamp');
        });

        Schema::create('message_files', function (Blueprint $table) {
            $table->id(); // Changed from uuid('id')->primary()

            // Changed from foreignUuid to unsignedBigInteger
            $table->unsignedBigInteger('message_id');

            $table->string('url');
            $table->string('name');
            $table->enum('type', ['image'])->default('image');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('message_id')
                ->references('id')
                ->on('messages')
                ->onDelete('cascade');

            // Indexes
            $table->index('message_id');
            $table->index('type');
        });

        Schema::create('shared_images', function (Blueprint $table) {
            $table->id(); // Changed from uuid('id')->primary()

            // Changed from foreignUuid to unsignedBigInteger
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('message_id');

            $table->string('url');
            $table->string('description')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('chat_id')
                ->references('id')
                ->on('chats')
                ->onDelete('cascade');

            $table->foreign('message_id')
                ->references('id')
                ->on('messages')
                ->onDelete('cascade');

            // Indexes
            $table->index('chat_id');
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_files');
        Schema::dropIfExists('shared_images');


    }
};
