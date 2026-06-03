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
        Schema::create('temp_zips', function (Blueprint $table) {
            $table->id('zip_id'); // Primary key
            $table->string('city_name', 255);
            $table->string('state_code', 10);
            $table->string('zip_code', 20);
            $table->integer('batch_id')->index(); // Batch tracking
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_zips');
    }
};
