<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// It enables Laravel’s queue system using the database driver.
// Instead of running tasks immediately (like sending emails, processing uploads), Laravel can:
// store jobs in the database
// process them in the background using a worker


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // The jobs table stores the jobs that have been queued by the Laravel queue system.
        // It is used to store the job ID, the connection name, the queue name, the payload, and the number of attempts.
        // The payload is the data that is passed to the job, and the attempts is the number of times the job has been attempted.
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // The job_batches table stores the batches of jobs that have been queued by the Laravel queue system.
        // It is used to store the batch ID, the name of the batch, the total number of jobs in the batch, the number of pending jobs, the number of failed jobs, and the failed job IDs.
        // The failed job IDs is a JSON array of the job IDs that have failed.
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        // The failed_jobs table stores the failed jobs that have been queued by the Laravel queue system.
        // It is used to store the job ID, the connection name, the queue name, the payload, and the exception.
        // The payload is the data that is passed to the job, and the exception is the exception that was thrown while processing the job.
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
