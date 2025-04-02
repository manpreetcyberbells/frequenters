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
        Schema::create('contest_voting_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contest_id');
            $table->unsignedBigInteger('contest_question_id');
            $table->unsignedBigInteger('contest_answer_id');
            $table->unsignedBigInteger('participant_id'); // The voter
            $table->timestamps();

            $table->foreign('contest_id')->references('id')->on('contests')->onDelete('cascade');
            $table->foreign('contest_question_id')->references('id')->on('contest_questions')->onDelete('cascade');
            $table->foreign('contest_answer_id')->references('id')->on('contest_answers')->onDelete('cascade');
            $table->foreign('participant_id')->references('id')->on('contest_participants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_voting_history');
    }
};
