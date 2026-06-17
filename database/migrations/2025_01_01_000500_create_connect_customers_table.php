<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connect_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->string('customer_phone');
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('total_conversations')->default(0);
            $table->unsignedInteger('total_messages')->default(0);

            // Opt-out / TCPA compliance (STOP / START)
            $table->boolean('opted_out')->default(false);
            $table->timestamp('opted_out_at')->nullable();

            $table->timestamps();

            $table->unique(['business_id', 'customer_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connect_customers');
    }
};
