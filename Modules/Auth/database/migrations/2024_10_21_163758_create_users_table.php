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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Standard auto-incrementing primary key (no need for ->unique() as id() is already unique)
            $table->string('name');
            $table->string('email')->unique();
            $table->string('guid')->nullable();
            $table->string('domain')->default('ub.edu.bz');
            $table->string('password')->nullable();
            $table->string('profile_picture')->nullable();
            $table->integer('menu_id')->nullable();
            $table->string('type');
            $table->unsignedBigInteger('cost_center_id')->nullable();

            // Changed to unsignedBigInteger for consistency
            $table->unsignedBigInteger('user_status_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();

            // Changed from uuid to unsignedBigInteger
            $table->unsignedBigInteger('campus_id')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('campus_id')
                ->references('id')
                ->on('campuses')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('user_status_id')
                ->references('id')
                ->on('user_statuses')
                ->onDelete('cascade');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });


        Schema::table('users', function (Blueprint $table) {
            $table->string('device_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};