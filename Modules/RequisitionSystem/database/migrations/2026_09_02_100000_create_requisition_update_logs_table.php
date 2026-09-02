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
        $this->ensureTableWithTimestamps('requisition_update_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')
                ->constrained('requisitions')
                ->cascadeOnDelete();
            $table->uuid('user_id');
            $table->boolean('submitted')->default(false);
            $table->string('event', 32)->default('updated');
            $table->text('summary')->nullable();
            $table->json('changes');
            $table->text('activity_comment')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('public.users')
                ->cascadeOnDelete();

            $table->index(['requisition_id', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('requisition_update_logs');
    }
};
