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
        Schema::create('contest_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contest_id');
            $table->text('question');
            $table->enum('question_type', ['multiple_choice', 'text_answer']); // Two types of questions
            $table->timestamps();
 
            $table->foreign('contest_id')->references('id')->on('contests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contest_questions');
    }
};

