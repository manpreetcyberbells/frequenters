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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('parent_business_id')->nullable()->after('id');
            $table->string('email', 191)->unique();
            $table->string('phone', 15)->nullable();
            $table->string('name')->nullable();
            $table->string('gender')->nullable();
            $table->string('profile_image',255)->nullable();
            $table->string('password',255)->nullable();
            $table->enum('role', ['customer', 'business', 'admin', 'manager', 'employee']);
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->enum('providers', ['facebook', 'google', 'apple', 'email']);
            $table->string('provider_id',255)->nullable();
            $table->string('apple_id')->nullable();
            $table->string('facebook_id')->nullable();
            $table->string('google_id')->nullable();
            $table->integer('otp')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->string('timezone',255)->default('America/New_York');
            $table->longText('preferences')->nullable()->comment('User preferences');
            $table->timestamps(); // Includes created_at and updated_at

            $table->foreign('parent_business_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
