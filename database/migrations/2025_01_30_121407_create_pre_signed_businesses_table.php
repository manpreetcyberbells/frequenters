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
        Schema::create('pre_signed_businesses', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('business_name', 255);
            $table->string('contact_person', 255);
            $table->string('email',191)->unique();
            $table->string('phone', 15)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip_code', 15)->nullable();
            $table->timestamp('signup_date')->default(now());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_signed_businesses');
    }
};
