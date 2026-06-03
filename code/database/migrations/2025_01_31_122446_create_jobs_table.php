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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->string('queue'); // Name of the queue
            $table->longText('payload'); // Job payload
            $table->tinyInteger('attempts')->unsigned(); // Number of attempts
            $table->integer('reserved_at')->unsigned()->nullable(); // Timestamp when job is reserved
            $table->integer('available_at')->unsigned(); // Timestamp when job is available
            $table->integer('created_at')->unsigned(); // Timestamp when job is created
        
            $table->index('queue'); // Index for queue performance
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
