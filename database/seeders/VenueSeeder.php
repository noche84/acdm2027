<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

/**
 * Lieux connus de l'ACDM et de FoliaMusica.
 * La clé « match » sert à l'import de l'ancienne table agenda (texte libre).
 */
class VenueSeeder extends Seeder
{
    public const VENUES = [
        'boverie' => [
            'name' => 'Musée de la Boverie', 'room' => 'Auditorium',
            'street' => 'Parc de la Boverie 3', 'postal_code' => '4020',
            'website' => 'https://www.laboverie.com',
            'access_info' => 'Accès PMR.',
        ],
        'palais' => [
            'name' => 'Palais provincial', 'room' => null,
            'street' => 'Place Saint-Lambert 18A', 'postal_code' => '4000',
            'website' => 'https://www.provincedeliege.be/votrepalais',
        ],
        'saint-vincent' => [
            'name' => 'Église Saint-Vincent', 'room' => 'Chapelle d\'hiver (chauffée)',
            'street' => 'Avenue Albert Mahiels', 'postal_code' => '4020',
        ],
        'saint-nicolas' => [
            'name' => 'Église Saint-Nicolas', 'room' => null,
            'street' => 'Rue Fosse-aux-Raines 9', 'postal_code' => '4020',
        ],
        'cathedrale' => [
            'name' => 'Cathédrale Saint-Paul', 'room' => null,
            'street' => 'Place de la Cathédrale', 'postal_code' => '4000',
        ],
        'villa-consulaire' => [
            'name' => 'Villa Consulaire', 'room' => null,
            'street' => 'Parc de la Boverie 1', 'postal_code' => '4020',
            'is_active' => false,
        ],
        'vie-wallonne' => [
            'name' => 'Musée de la Vie wallonne', 'room' => 'Auditorium',
            'street' => 'Cour des Mineurs', 'postal_code' => '4000',
            'is_active' => false,
        ],
        'curtius' => [
            'name' => 'Grand Curtius', 'room' => null,
            'street' => 'Féronstrée 136', 'postal_code' => '4000',
            'is_active' => false,
        ],
        'mamac' => [
            'name' => 'MAMAC', 'room' => 'Musée d\'Art moderne et d\'Art contemporain',
            'street' => 'Parc de la Boverie', 'postal_code' => '4020',
            'is_active' => false,
        ],
        'bal' => [
            'name' => 'BAL – Musée des Beaux-Arts de Liège', 'room' => null,
            'street' => 'Féronstrée 86', 'postal_code' => '4000',
            'is_active' => false,
        ],
        'saint-sacrement' => [
            'name' => 'Église du Saint-Sacrement', 'room' => null,
            'street' => 'Boulevard d\'Avroy 132', 'postal_code' => '4000',
            'is_active' => false,
        ],
        'saint-denis' => [
            'name' => 'Collégiale Saint-Denis', 'room' => null,
            'street' => 'Place Saint-Denis', 'postal_code' => '4000',
            'is_active' => false,
        ],
    ];

    public function run(): void
    {
        foreach (self::VENUES as $data) {
            Venue::updateOrCreate(
                ['name' => $data['name']],
                $data + ['city' => 'Liège', 'is_active' => true],
            );
        }
    }

    /** Associe le texte libre de l'ancien champ « lieu » à une clé de VENUES. */
    public static function matchLegacy(string $lieu): ?string
    {
        $l = mb_strtolower(trim($lieu));

        return match (true) {
            str_contains($l, 'villa') => 'villa-consulaire',
            str_contains($l, 'boverie') => 'boverie',
            str_contains($l, 'vie wallonne') => 'vie-wallonne',
            str_contains($l, 'palais') => 'palais',
            str_contains($l, 'saint-vincent') || str_contains($l, 'saint vincent') => 'saint-vincent',
            str_contains($l, 'saint-nicolas') || str_contains($l, 'saint nicolas') => 'saint-nicolas',
            str_contains($l, 'cathédrale') || str_contains($l, 'cathedrale') => 'cathedrale',
            str_contains($l, 'curtius') => 'curtius',
            str_contains($l, 'sacrement') => 'saint-sacrement',
            str_contains($l, 'saint denis') || str_contains($l, 'saint-denis') => 'saint-denis',
            $l === 'mamac' => 'mamac',
            $l === 'bal' => 'bal',
            default => null,
        };
    }
}
