<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// This table is used by Laravel to store cached data in the database (instead of files, Redis, etc.).

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The cache table stores the cached data in the database.
        // It is used to store the cache key, the cache value, and the expiration time.
        // The cache value is the data that is cached, and the expiration time is the time when the cache expires.
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        // The cache_locks table stores the locks on the cached data in the database.
        // It is used to store the cache key, the owner of the lock, and the expiration time.
        // The owner is the user who has the lock, and the expiration time is the time when the lock expires.
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
