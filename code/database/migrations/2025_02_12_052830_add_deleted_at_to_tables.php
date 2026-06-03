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
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('business_addresses', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('business_images', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('business_hours', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('business_addresses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('business_images', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('business_hours', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
    
};
