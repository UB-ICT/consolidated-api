<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('suppliers')) {
            return;
        }

        Schema::connection($this->connection)->create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person');
            $table->string('phone_number');
            $table->string('email')->unique();
            $table->string('TIN')->unique();
            $table->integer('status_id');
            $table->timestamps();

            // Foreign key constraint for your users table
            $table->foreignId('status_id')
                ->default(1)
                ->constrained('statuses')
                ->onDelete('restrict');

            $table->foreignUuid('approved_by_user_id')
                ->nullable()
                ->constrained('public.users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('suppliers');
    }
};
