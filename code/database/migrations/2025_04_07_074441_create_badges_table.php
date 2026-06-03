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
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('tier', ['entry', 'mid', 'top']); //level
            $table->string('icon',191)->unique();
            $table->string('slug')->unique();
            $table->text('criteria_description'); // Optional
            $table->integer('validity_months')->default(12); // time-sensitive
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
