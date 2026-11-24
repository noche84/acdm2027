<?php

namespace Database\Seeders;

use App\Enums\Brand;
use App\Models\Series;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class SeriesSeeder extends Seeder
{
    public function run(): void
    {
        $boverie = Venue::where('name', 'Musée de la Boverie')->value('id');
        $palais = Venue::where('name', 'Palais provincial')->value('id');
        $vincent = Venue::where('name', 'Église Saint-Vincent')->value('id');

        $series = [
            [
                'slug' => 'concerts-du-dimanche-matin', 'brand' => Brand::Acdm,
                'name' => 'Concerts du Dimanche Matin', 'sort' => 1,
                'default_time' => '11:00', 'default_venue_id' => $boverie,
                'description' => null,
            ],
            [
                'slug' => 'votre-palais', 'brand' => Brand::FoliaMusica,
                'name' => 'Concerts « Votre Palais »', 'sort' => 10,
                'default_time' => '15:00', 'default_venue_id' => $palais,
                'default_price_info' => 'Entrée gratuite avec la visite de « Votre Palais »',
                'website' => 'https://www.provincedeliege.be/votrepalais',
                'description' => '<p>Une nouvelle dynamique et une nouvelle offre culturelle en vue de rendre le Palais provincial aux Liégeois, de l\'ouvrir à de nouveaux publics et de promouvoir les artistes.</p><p>Tous les concerts sont suivis d\'un bar organisé par la Province de Liège.</p>',
            ],
            [
                'slug' => 'saint-vincent', 'brand' => Brand::FoliaMusica,
                'name' => 'Concerts à Saint-Vincent', 'sort' => 20,
                'default_time' => '15:00', 'default_venue_id' => $vincent,
                'description' => '<p>Dans la chapelle d\'hiver (chauffée) de l\'église Saint-Vincent.</p>',
            ],
            [
                'slug' => 'concerts-exceptionnels', 'brand' => Brand::FoliaMusica,
                'name' => 'Concerts exceptionnels', 'sort' => 30,
                'description' => null,
            ],
        ];

        foreach ($series as $data) {
            Series::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
