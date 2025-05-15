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
        Schema::table('contests', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('contest_answers', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('contest_participants', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('contest_questions', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('contest_question_options', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('contest_voting_history', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contest_related_tables', function (Blueprint $table) {
            //
        });
    }
};
