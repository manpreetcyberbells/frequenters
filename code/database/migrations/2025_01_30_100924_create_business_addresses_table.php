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
        Schema::create('business_addresses', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->longText('location')->nullable();
            $table->string('address_line1', 255);
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 255);
            $table->string('state', 255);
            $table->string('country', 255);
            $table->string('zip_code', 255);
            $table->float('latitude', 10, 6)->nullable(); // Precision for GPS coordinates
            $table->float('longitude', 10, 6)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_addresses');
    }
};
