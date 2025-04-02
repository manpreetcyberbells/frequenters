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
        Schema::create('contest_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contest_id');
            $table->unsignedBigInteger('contest_question_id');
            $table->unsignedBigInteger('participant_id'); // Link to `contest_participants`
            $table->text('answer')->nullable();
            $table->unsignedBigInteger('contest_question_option_id')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestamps();
 
            $table->foreign('contest_id')->references('id')->on('contests')->onDelete('cascade');
            $table->foreign('contest_question_id')->references('id')->on('contest_questions')->onDelete('cascade');
            $table->foreign('participant_id')->references('id')->on('contest_participants')->onDelete('cascade');
            $table->foreign('contest_question_option_id')->references('id')->on('contest_question_options')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contest_answers');
    }
};

