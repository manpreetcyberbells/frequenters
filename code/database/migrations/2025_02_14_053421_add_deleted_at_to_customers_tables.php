<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Schema::table('customer_badges', function (Blueprint $table) {
        //     $table->softDeletes();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Schema::table('customer_badges', function (Blueprint $table) {
        //     $table->dropSoftDeletes();
        // });
    }
};
