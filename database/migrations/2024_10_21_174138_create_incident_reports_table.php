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
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('description');
            $table->string('disposition');
            $table->string('case_number');
            $table->string('action');
            $table->string('location');
            $table->string('uploaded_by');
            $table->integer('frequency');
            $table->dateTime('incident_reoccured');
            $table->foreignUuid('incident_status_id')->constrained('incident_statuses')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignUuid('building_id')->constrained('buildings')->onDelete('cascade');
            $table->uuid('incident_type_id'); // Changed to uuid to match referenced column
            $table->timestamps();

            $table->foreign('incident_type_id')
                ->references('id')
                ->on('incident_types')
                ->onDelete('cascade');
        });

        Schema::create('incident_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('incident_report_id');
            $table->string('path');
            $table->string('name');
            $table->timestamps();

            $table->foreign('incident_report_id')
                ->references('id')
                ->on('incident_reports')
                ->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_files');
        Schema::dropIfExists('incident_reports');
    }
};