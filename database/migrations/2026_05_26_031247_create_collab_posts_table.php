<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collab_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('users')->cascadeOnDelete();
            $table->enum('post_type', ['request', 'offer']);
            $table->string('headline', 255);
            $table->text('description');
            $table->json('target_industries'); // multiple industries allowed
            $table->unsignedTinyInteger('duration_days')->default(7);
            $table->timestamp('expires_at');
            $table->enum('contact_type', ['in_app', 'direct'])->default('in_app');
            // Direct contact fields (nullable — only if contact_type = 'direct')
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            // Location snapshot at time of posting
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expires_at', 'is_active']);
            $table->index(['business_id']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collab_posts');
    }
};