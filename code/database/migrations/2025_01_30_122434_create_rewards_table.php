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
        Schema::create('rewards', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->string('reward_name', 255);
            $table->integer('points_required')->nullable();
            $table->text('qr_code')->nullable();
            $table->json('qr_code_payload')->nullable();
            $table->boolean('is_exclusive')->default(1);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('banner_image')->nullable()->after('expiry_date');
            $table->string('description')->nullable()->after('banner_image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
