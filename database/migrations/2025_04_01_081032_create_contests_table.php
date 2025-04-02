<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('contests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id'); // Business who created the contest
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('banner_image')->nullable(); // Contest banner image
            $table->enum('status', ['open', 'close', 'completed'])->default('open');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('close_description')->nullable();
            $table->date('close_start_date')->nullable();
            $table->date('close_end_date')->nullable();
            $table->time('close_start_time')->nullable();
            $table->time('close_end_time')->nullable();
            $table->integer('max_participants')->nullable();
            $table->enum('reward_type', ['points', 'discount', 'free_item']);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('users')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contests');
    }
};

