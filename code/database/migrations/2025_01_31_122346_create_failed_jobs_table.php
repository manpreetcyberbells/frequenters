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
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->string('uuid')->unique(); // Unique UUID
            $table->text('connection'); // Connection name
            $table->text('queue'); // Queue name
            $table->longText('payload'); // Job payload
            $table->longText('exception'); // Exception details
            $table->timestamp('failed_at')->useCurrent(); // Default timestamp when job fails
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
