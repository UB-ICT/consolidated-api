<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection used by this migration.
     * Set to 'pgsql' (your primary auth/core database connection).
     */
    protected $connection = 'pgsql';

    /**
     * Run the migrations.
     * Generates the API token storage table for Laravel Sanctum authentication.
     */
    public function up(): void
    {
        // =========================================================================
        // PERSONAL ACCESS TOKENS TABLE
        // Stores API tokens assigned to users (or other authenticatable models).
        // =========================================================================
        if (!Schema::connection($this->connection)->hasTable('personal_access_tokens')) {
            Schema::connection($this->connection)->create('personal_access_tokens', function (Blueprint $table) {
                $table->id();                                    // Primary key auto-increment ID

                // Polymorphic relation columns: 'tokenable_type' (string) and 'tokenable_id' (UUID/Integer)
                // This allows tokens to belong to Users, Admins, or any other model.
                $table->nullableMorphs('tokenable');

                $table->string('name');                          // Custom descriptor for the token (e.g., 'Mobile App', 'Developer Key')
                $table->string('token', 64)->unique();          // The hashed token value used to authenticate API requests
                $table->text('abilities')->nullable();           // JSON array defining token permissions/scopes
                $table->timestamp('last_used_at')->nullable();    // Automatically updates whenever a request uses this token
                $table->timestamp('expires_at')->nullable();      // Optional expiration date for the token lifetime
                $table->timestamps();                            // created_at and updated_at timestamps
            });
        }
    }

    /**
     * Reverse the migrations.
     * Drops the personal access tokens table if it exists.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('personal_access_tokens');
    }
};
