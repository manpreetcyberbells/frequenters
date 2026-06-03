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
        Schema::create('text_codes', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('code', 20)->unique(); // Unique text code
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_used')->default(0); // Flag to check if used
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('text_codes');
    }
};
