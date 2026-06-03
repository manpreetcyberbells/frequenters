<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CitiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Run the command for only => "php artisan db:seed --class=CitiesTableSeeder"
    */
    public function run(): void
    {

        ini_set('memory_limit', '-1'); // Disable memory limit

        Schema::disableForeignKeyConstraints();
        DB::table('cities')->truncate(); // Clears old data

        // Load cities data from an external file to reduce memory usage
        $cities = include(database_path('seeders/data/cities.php')); // Load an array of cities

        $chunks = array_chunk($cities, 1000); // Break into chunks

        foreach ($chunks as $chunk) {
            DB::table('cities')->insert($chunk);
        }

        Schema::enableForeignKeyConstraints();
    }
}
