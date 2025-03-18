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
            $table->id();
            $table->string('report'); // change to description
            $table->string('disposition');
            $table->string('case_number');
            $table->string('action');
            $table->string('location');
            $table->string('uploaded_by');
            $table->integer('frequency');
            $table->dateTime('incident_reoccured');
            $table->string('incident_files')->nullable();
            $table->foreignId('incident_status_id')->constrained('incident_statuses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('building_id')->constrained('buildings')->onDelete('cascade');
            $table->string('incident_type_id')->onDelete('cascade');
        });

        Schema::create('incident_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incident_report_id');
            $table->string('path');
            $table->string('name');
            $table->timestamps();

            $table->foreign('incident_report_id')->references('id')->on('incident_reports')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('incident_reports', function (Blueprint $table) {
            $table->dropColumn('incident_files');
        });
    }
};