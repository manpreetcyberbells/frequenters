<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TempZipsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Run the command for only => "php artisan db:seed --class=TempZipsTableSeeder"
    */
    public function run(): void
    {

        ini_set('memory_limit', '-1'); // Disable memory limit

        Schema::disableForeignKeyConstraints();
        DB::table('temp_zips')->truncate(); // Clears old data

        // Load cities data from an external file to reduce memory usage
        $temzips_data = include(database_path('seeders/data/temp_zips.php')); // Load an array of cities

        $chunks = array_chunk($temzips_data, 1000); // Break into chunks

        foreach ($chunks as $chunk) {
            DB::table('temp_zips')->insert($chunk);
        }

        Schema::enableForeignKeyConstraints();
    }
}
