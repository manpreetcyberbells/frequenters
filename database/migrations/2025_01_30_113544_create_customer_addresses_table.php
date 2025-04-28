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
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('customer_id'); // Foreign key to users table
            $table->string('address_line1', 255);
            $table->string('address_line2', 255)->nullable();
            $table->string('zip_code', 45);
            $table->string('city', 255);
            $table->string('state', 255);
            $table->string('country', 255);
            $table->integer('radius')->default(5);
            $table->float('latitude', 10, 6)->nullable(); // Precision for GPS coordinates
            $table->float('longitude', 10, 6)->nullable();
            $table->enum('is_current', ['0', '1'])->default('0'); // 0 = disabled
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
