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
            $table->id(); // Standard auto-incrementing primary key
            $table->string('report');
            $table->text('description');
            $table->string('disposition');
            $table->string('case_number');
            $table->string('action');
            $table->string('location');
            $table->string('uploaded_by');
            $table->integer('frequency');
            $table->dateTime('incident_reoccured');

            // Foreign keys using unsignedBigInteger
            $table->unsignedBigInteger('incident_status_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('campus_id');
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('incident_type_id');

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('incident_status_id')
                ->references('id')
                ->on('incident_statuses')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('campus_id')
                ->references('id')
                ->on('campuses')
                ->onDelete('cascade');

            $table->foreign('building_id')
                ->references('id')
                ->on('buildings')
                ->onDelete('cascade');

            $table->foreign('incident_type_id')
                ->references('id')
                ->on('incident_types')
                ->onDelete('cascade');
        });

        Schema::create('incident_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incident_report_id'); // Changed to match incident_reports.id
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