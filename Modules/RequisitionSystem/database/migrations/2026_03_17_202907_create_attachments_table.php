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
        if (Schema::connection($this->connection)->hasTable('attachments')) {
            return;
        }

        Schema::connection($this->connection)->create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path');
            $table->timestamp('uploaded_at')->useCurrent();

            // 1. Define uploaded_by as a UUID column to match your users table ID
            $table->uuid('uploaded_by');

            $table->foreignId('requisition_id')->constrained('requisitions')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers');

            // 2. Map the cross-schema constraint to public.users
            $table->foreign('uploaded_by')
                ->references('id')
                ->on('public.users')
                ->onDelete('restrict'); // Protects attachments from disappearing if a user is deleted
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('attachments');
    }
};
