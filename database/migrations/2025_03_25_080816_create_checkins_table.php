<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users', 'id')->onDelete('cascade');
            $table->timestamp('check_in_time')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('checkins');
    }
};

