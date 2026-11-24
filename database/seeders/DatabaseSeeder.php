<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Données de base : lieux, séries, pages et partenaires.
     * Les concerts et les abonnés viennent de « php artisan acdm:import-legacy ».
     * L'administrateur se crée avec « php artisan acdm:user ».
     */
    public function run(): void
    {
        $this->call([
            VenueSeeder::class,
            SeriesSeeder::class,
            PageSeeder::class,
            PartnerSeeder::class,
        ]);
    }
}
