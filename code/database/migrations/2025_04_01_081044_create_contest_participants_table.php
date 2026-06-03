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
        Schema::create('contest_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contest_id');
            $table->enum('customer_status', ['daily', 'regular', 'frequenters']);
            $table->integer('participation_count')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            
            $table->foreign('contest_id')->references('id')->on('contests')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->nullOnDelete();

            // Ensure unique contest + customer status
            $table->unique(['contest_id', 'customer_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contest_participants');
    }
};

