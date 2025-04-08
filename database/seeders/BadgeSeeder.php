<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
    */
    public function run(): void
    {
        $badges = [
            [
                'name' => 'Frequenters Enthusiasts',
                'tier' => 'entry',
                'slug' => 'frequenters_enthusiasts',
                'criteria_description' => 'Starts when they sign up',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters Pioneers',
                'tier' => 'entry',
                'slug' => 'frequenters_pioneers',
                'criteria_description' => '100 points AND 3 check-ins',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters Contributors',
                'tier' => 'entry',
                'slug' => 'frequenters_contributors',
                'criteria_description' => '500 points AND 10 check-ins AND 1 Review AND 1 vote in the contest',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters Explorers',
                'tier' => 'entry',
                'slug' => 'frequenters_explorers',
                'criteria_description' => '1000 points AND 5 unique check-ins',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters Rising Stars',
                'tier' => 'entry',
                'slug' => 'frequenters_rising_stars',
                'criteria_description' => '5000 points in last 12 months AND 100 check-ins, AND 5 reviews AND 1 contest submission OR 20 votes',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters Insiders',
                'tier' => 'mid',
                'slug' => 'frequenters_insiders',
                'criteria_description' => '10,000 points OR (250 check-ins)',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters Trailblazers',
                'tier' => 'mid',
                'slug' => 'frequenters_trailblazers',
                'criteria_description' => '15,000 points or 350 check-ins and 5 contest submissions',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters A-Listers',
                'tier' => 'mid',
                'slug' => 'frequenters_a_listers',
                'criteria_description' => 'Trailblazer + Contest Winner',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters Master',
                'tier' => 'mid',
                'slug' => 'frequenters_master',
                'criteria_description' => 'A-Lister + 25000 lifetime points',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters VIP',
                'tier' => 'mid',
                'slug' => 'frequenters_vip',
                'criteria_description' => '25,000 points, 5 contest winner, 20+ ratings, 20+votes',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters All-Stars',
                'tier' => 'top',
                'slug' => 'frequenters_all_stars',
                'criteria_description' => 'VIP + Photo Uploads + Referred friends + 10 contest wins',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters Icons',
                'tier' => 'top',
                'slug' => 'frequenters_icons',
                'criteria_description' => 'All-Stars + 50,000 points',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters Legends',
                'tier' => 'top',
                'slug' => 'frequenters_legends',
                'criteria_description' => 'Icon + 500 checkins in more than 1 cities',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters Royalty',
                'tier' => 'top',
                'slug' => 'frequenters_royalty',
                'criteria_description' => 'Legend + 10 cities 1000 check ins',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequenters Elite',
                'tier' => 'top',
                'slug' => 'frequenters_elite',
                'criteria_description' => 'Royalty + 10,000 check ins, 100 contest submissions, 50 reviews',
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($badges as $badge) {
            $slug = $badge['slug'];
            $iconPath = "badges/{$slug}.png";

            DB::table('badges')->insert([
                'name' => $badge['name'],
                'tier' => $badge['tier'],
                'icon' => ("storage/{$iconPath}"),
                'slug' => $slug,
                'criteria_description' => $badge['criteria_description'],
                'validity_months' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

}
