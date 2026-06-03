<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contest_participants', function (Blueprint $table) {
            $table->dropUnique('contest_participants_contest_id_customer_status_unique');
        });
    }

    public function down(): void
    {
        Schema::table('contest_participants', function (Blueprint $table) {
            $table->unique(['contest_id', 'customer_status']);
        });
    }
};
