<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'ubportal';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('menu_items')) {
            return;
        }
        // Stores navigational items shown in the portal UI.
        Schema::create('menu_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('label');
            $table->string('path');
            $table->string('icon')->nullable();

            // Optional role-based visibility for the menu item.
            $table->foreignUuid('role_id')->nullable()->constrained('roles')->onDelete('set null');

            // Parent menu item for nested navigation trees.
            $table->uuid('parent_id')->nullable();

            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Adds self-referencing foreign key after table creation.
        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('menu_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the menu items table.
        Schema::dropIfExists('menu_items');
    }
};
