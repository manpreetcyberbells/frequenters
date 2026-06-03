<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            // $table->unsignedBigInteger('business_id');
            $table->foreignId('business_id')->constrained('users', 'id')->onDelete('cascade');
            $table->string('title'); // e.g., "40% OFF up to $100"
            $table->text('description')->nullable();
            $table->decimal('discount_percentage', 5,2);
            $table->decimal('discount_cap', 10,2)->nullable();
            $table->enum('applicable_users', ['new', 'regular', 'daily']);  
            $table->enum('offer_time', ['all_day', 'morning_only', 'evening_only']);
            $table->date('campaign_start_date');
            $table->time('campaign_start_time');
            $table->date('campaign_end_date')->nullable();
            $table->time('campaign_end_time')->nullable();
            $table->enum('applicable_days', ['Entire Week', 'Mon-Thu', 'Fri-Sun']);
            $table->decimal('min_order_value', 10,2);
            $table->text('terms_and_conditions')->nullable();
            $table->string('banner_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('offers');
    }
};

