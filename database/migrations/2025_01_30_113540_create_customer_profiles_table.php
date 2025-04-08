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
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('customer_id'); // Foreign key to users table
            $table->integer('points_balance')->default(0);
            $table->text('qr_code_hash')->nullable();
            $table->string('customer_uid',55)->nullable();
            $table->date('birthday')->nullable();
            $table->date('anniversary')->nullable();
            $table->longText('preferences')->nullable();
            $table->longText('cities_of_interest')->nullable();
            $table->integer('total_rewards_earned')->default(0);
            $table->integer('total_rewards_redeemed')->default(0);
            $table->enum('preferred_notification_channel', ['email', 'SMS', 'push'])->default('email');
            $table->string('preferred_language', 10)->default('en'); // en // spanish es
            $table->enum('push_notification', ['0', '1'])->default('1'); // 1 = enabled
            $table->enum('location', ['0', '1'])->default('0'); // 0 = disabled
            $table->enum('show_personal_info', ['0', '1'])->default('1'); // 1 = enabled
            $table->string('anonymous_name',55)->nullable();
            $table->string('anonymous_image',255)->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
