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
        Schema::create('cities', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('city_name', 255)->notNullable();
            $table->string('state_code', 55)->nullable();
            $table->string('state', 255)->nullable();
            $table->string('country', 255)->default('USA');
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('county_name', 45)->nullable();
            $table->string('county_fips', 5)->nullable();
            $table->decimal('population', 12, 2)->nullable();
            $table->decimal('population_proper', 12, 2)->nullable();
            $table->float('density')->nullable();
            $table->float('age_median')->nullable();
            $table->float('income_household_median')->nullable();
            $table->string('timezone', 120)->nullable();
            $table->float('education_college_or_above')->nullable();
            $table->float('unemployment_rate')->nullable();
            $table->float('race_white')->nullable();
            $table->float('race_black')->nullable();
            $table->float('race_asian')->nullable();
            $table->float('race_native')->nullable();
            $table->float('race_pacific')->nullable();
            $table->float('race_other')->nullable();
            $table->float('race_multiple')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
