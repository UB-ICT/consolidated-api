<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\RequisitionSystem\Database\Migrations\EnsuresTimestamps;

return new class extends Migration
{
    use EnsuresTimestamps;

    protected $connection = 'porsql';

    public function up(): void
    {
        $this->ensureTableWithTimestamps('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person');
            $table->string('phone_number');
            $table->string('email')->unique();
            $table->string('TIN')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

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

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('suppliers');
    }
};
