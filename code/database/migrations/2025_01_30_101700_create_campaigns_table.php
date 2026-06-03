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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name', 255)->notNullable();
            $table->text('description')->nullable();
            $table->dateTime('start_date')->notNullable();
            $table->dateTime('end_date')->notNullable();
            $table->enum('status', ['active', 'inactive', 'completed'])->default('active');
            $table->integer('total_notifications')->default(0);
            $table->integer('successful_deliveries')->default(0);
            $table->enum('goal', ['engagement', 'conversion', 'retention'])->default('engagement');
            $table->decimal('click_through_rate', 5, 2)->default(0.00)->comment('CTR percentage');
            $table->decimal('engagement_rate', 5, 2)->default(0.00)->comment('Engagement percentage');
            $table->decimal('roi', 10, 2)->default(0.00)->comment('Return on investment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
