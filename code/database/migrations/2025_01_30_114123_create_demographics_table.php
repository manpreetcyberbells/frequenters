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
        Schema::create('demographics', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('city_id'); // Foreign key to cities table
            $table->integer('year'); // Year for the demographic data
            $table->float('age_median')->nullable();
            $table->float('income_household_median')->nullable();
            $table->float('education_college_or_above')->nullable();
            $table->float('unemployment_rate')->nullable();
            $table->float('race_white')->nullable();
            $table->float('race_black')->nullable();
            $table->float('race_asian')->nullable();
            $table->float('race_native')->nullable();
            $table->float('race_pacific')->nullable();
            $table->float('race_other')->nullable();
            $table->float('race_multiple')->nullable();
            // $table->timestamps();

            // Foreign key constraints
            // $table->foreign('city_id')
            //       ->references('id')
            //       ->on('cities')
            //       ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demographics');
    }
};
