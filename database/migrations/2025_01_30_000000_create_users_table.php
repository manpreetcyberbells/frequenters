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
            $table->string('email', 191)->unique();
            $table->string('phone', 15)->nullable();
            $table->string('name')->nullable();
            $table->string('gender')->nullable();
            $table->string('profile_image',255)->nullable();
            $table->string('password');
            $table->enum('role', ['customer', 'business', 'admin']);
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->enum('providers', ['facebook', 'google', 'apple', 'email']);
            $table->string('apple_id')->nullable();
            $table->string('facebook_id')->nullable();
            $table->string('google_id')->nullable();
            $table->integer('otp')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->longText('preferences')->nullable()->comment('User preferences');
            $table->timestamps(); // Includes created_at and updated_at
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
