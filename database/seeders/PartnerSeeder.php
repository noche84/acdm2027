<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/** Partenaires repris de l'ancienne page « Nos partenaires ». À relire. */
class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $partners = [
            ['Province de Liège – Culture', 'province-liege.png', 'https://www.provincedeliege.be/fr/culture', 'pouvoirs-publics', 1],
            ['Fédération Wallonie-Bruxelles', 'federation-wallonie-bruxelles.png', 'https://www.federation-wallonie-bruxelles.be', 'pouvoirs-publics', 2],
            ['Ville de Liège – Culture', 'ville-liege-culture.jpg', 'https://www.liege.be/fr/vie-communale/services-communaux/culture', 'pouvoirs-publics', 3],
            ['Musée de la Boverie', 'la-boverie.jpg', 'https://www.laboverie.com', 'lieux', 10],
            ['Votre Palais – Palais provincial', 'votre-palais.jpg', 'https://www.provincedeliege.be/votrepalais', 'lieux', 11],
            ['Consulat honoraire d\'Italie à Liège', 'consulat-italie-liege.jpg', 'http://www.liegi-onorario.be', 'soutien', 20],
            ['Bovy Piano', 'bovy-piano.png', 'https://www.bovypiano.be', 'soutien', 21],
            ['CPH Banque', 'cph.png', null, 'soutien', 22],
            ['Oenogea', 'oenogea.png', 'https://oenogea.be', 'soutien', 23],
            ['RCF Liège', 'rcf.jpg', 'https://www.rcf.be', 'medias', 30],
        ];

        foreach ($partners as [$name, $file, $url, $category, $sort]) {
            $path = null;
            $source = database_path('seeders/assets/partners/'.$file);
            if (File::exists($source)) {
                $path = 'partners/'.$file;
                Storage::disk('public')->put($path, File::get($source));
            }

            Partner::firstOrCreate(['name' => $name], [
                'logo' => $path,
                'url' => $url,
                'category' => $category,
                'sort' => $sort,
                'is_active' => true,
            ]);
        }
    }
}
