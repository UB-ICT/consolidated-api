<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('menus')) {
            return;
        }
        // Stores navigational items shown in the portal UI.
        Schema::create('menus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('label');
            $table->string('path');
            $table->string('type')->default('application'); // e.g., 'application', 'user-menu'
            $table->string('icon')->nullable();

            $table->string('status')->default('active');
            $table->string('description')->nullable();
            $table->string('category')->nullable();

            // Optional role-based visibility for the menu item.
            $table->foreignUuid('role_id')->nullable()->constrained('roles')->onDelete('set null');

            // Parent menu item for nested navigation trees.
            $table->uuid('parent_id')->nullable();

            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Adds self-referencing foreign key after table creation.
        Schema::table('menus', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('menus')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the menu items table.
        Schema::dropIfExists('menus');
    }
};
