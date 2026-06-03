<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Drop old columns if they exist
            if (Schema::hasColumn('business_profiles', 'category_idOLD')) {
                $table->dropColumn('category_idOLD');
            }
            if (Schema::hasColumn('business_profiles', 'child_category_idOLD')) {
                $table->dropColumn('child_category_idOLD');
            }

            // Add new category_id column with foreign key
            if (!Schema::hasColumn('business_profiles', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            }

            // Add new child_category_id column with foreign key
            if (!Schema::hasColumn('business_profiles', 'child_category_id')) {
                $table->foreignId('child_category_id')->nullable()->constrained('categories')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('business_profiles', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('business_profiles', 'child_category_id')) {
                $table->dropForeign(['child_category_id']);
                $table->dropColumn('child_category_id');
            }
        });
    }
};



