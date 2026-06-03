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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2); // Transaction amount
            $table->string('payment_gateway', 50); // Payment gateway name
            $table->timestamp('transaction_date')->default(DB::raw('CURRENT_TIMESTAMP')); // Default current timestamp
            $table->integer('transaction_year'); // Year of transaction
            $table->enum('status', ['pending', 'completed', 'refunded'])->default('completed'); // Transaction status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
