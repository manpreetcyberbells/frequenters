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
        Schema::create('event_reservations', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('event_id'); // Foreign key to events table
            $table->unsignedBigInteger('customer_id'); // Foreign key to users table
            $table->enum('status', ['confirmed', 'canceled', 'attended'])->default('confirmed');
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('event_id')
                  ->references('id')
                  ->on('events')
                  ->onDelete('cascade');

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
        Schema::dropIfExists('event_reservations');
    }
};
