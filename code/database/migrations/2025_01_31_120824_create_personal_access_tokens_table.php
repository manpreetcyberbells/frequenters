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
        // Schema::create('personal_access_tokens', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('tokenable_id'); // Explicit tokenable_id
        //     $table->string('tokenable_type', 255); // Explicit tokenable_type
        //     $table->string('name');
        //     $table->string('token', 64)->unique();
        //     $table->text('abilities')->nullable();
        //     $table->timestamp('last_used_at')->nullable();
        //     $table->timestamp('expires_at')->nullable();
        //     $table->timestamps();
        //     // Index for faster lookups
        //     $table->index(['tokenable_id', 'tokenable_type']);
        // });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned(); // Auto-increment id (bigint UNSIGNED)
            $table->string('tokenable_type'); // varchar(255)
            $table->bigInteger('tokenable_id')->unsigned(); // bigint UNSIGNED
            $table->string('name'); // varchar(255)
            $table->string('token', 64); // varchar(64)
            $table->text('abilities')->nullable(); // text, NULLABLE
            $table->timestamp('last_used_at')->nullable(); // timestamp, NULLABLE
            $table->timestamp('expires_at')->nullable(); // timestamp, NULLABLE
            $table->timestamps(0); // created_at and updated_at (timestamp, NULLABLE)

            // Indexes
            $table->primary('id');
            $table->index(['tokenable_type', 'tokenable_id'], 'personal_access_tokens_tokenable_type_tokenable_id_index');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
