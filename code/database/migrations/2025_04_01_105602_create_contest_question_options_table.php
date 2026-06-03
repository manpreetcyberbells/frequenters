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
        Schema::create('contest_question_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contest_question_id'); // FK to contest_questions table
            $table->string('option_text'); // Answer Option
            $table->timestamps();

            $table->foreign('contest_question_id')->references('id')->on('contest_questions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_question_options');
    }
};
