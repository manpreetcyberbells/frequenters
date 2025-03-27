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
        Schema::create('events', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_city_id')->nullable()->constrained('cities')->onDelete('cascade');
            $table->string('event_name', 255);
            $table->string('venue_name', 255)->nullable();
            $table->string('venue_location', 255);
            $table->text('event_description')->nullable();
            $table->integer('capacity')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->enum('target_tier', ['new', 'regular', 'daily'])->nullable();
            $table->enum('status', ['draft', 'published'])->nullable();
            $table->string('event_banner', 255)->nullable();
            //$table->unsignedBigInteger('target_city_id')->nullable(); // Foreign key to cities table
            $table->string('virtual_event_link', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
