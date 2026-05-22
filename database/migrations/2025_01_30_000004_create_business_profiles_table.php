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
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('business_id'); // Foreign key to users table
            $table->string('business_name', 255)->notNullable();
            $table->string('industry_type', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('website', 255)->nullable();
            $table->json('social_links')->nullable();
            $table->text('qr_code_hash')->nullable();
            $table->string('business_uid',55)->nullable();
            $table->integer('points_given')->default(0);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->integer('monthly_points_available')->default(0);
            $table->integer('points_redeemed')->default(0);
            // $table->float('latitude', 10, 6)->nullable(); // Precision for GPS coordinates
            // $table->float('longitude', 10, 6)->nullable();
            $table->integer('points_per_checkin')->default(0);
            $table->unsignedInteger('tier_regular_min')->default(3)->after('points_per_checkin');
            $table->unsignedInteger('tier_daily_min')->default(10)->after('tier_regular_min');
            $table->decimal('conversion_rate', 5, 2)->default(0.00);
            $table->integer('total_checkins')->default(0);
            $table->integer('total_rewards_redeemed')->default(0);
            $table->integer('category_id')->nullable();
            $table->integer('child_category_id')->nullable();
            $table->string('logo', 55)->nullable();
            $table->string('owner_name', 55)->notNullable();
            $table->string('owner_email', 55)->nullable();
            $table->string('owner_phone', 15)->notNullable();
            $table->string('preferred_language', 10)->default('en'); // en // spanish es
            $table->enum('push_notification', ['0', '1'])->default('1'); // 1 = enabled
            $table->string('fcm_token',255)->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('business_id')
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
        Schema::dropIfExists('business_profiles');
    }
};
