<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary(); // Primary key (UUID)
            $table->string('name'); // Name of the job batch
            $table->integer('total_jobs'); // Total jobs in the batch
            $table->integer('pending_jobs'); // Jobs still pending
            $table->integer('failed_jobs'); // Failed job count
            $table->longText('failed_job_ids'); // Stores failed job IDs
            $table->mediumText('options')->nullable(); // Batch options (nullable)
            $table->integer('cancelled_at')->nullable(); // Cancellation timestamp (nullable)
            $table->integer('created_at'); // Timestamp when batch was created
            $table->integer('finished_at')->nullable(); // Timestamp when batch completed (nullable)
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_batches');
    }
};
