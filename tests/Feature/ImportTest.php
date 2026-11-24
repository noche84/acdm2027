<?php

namespace Tests\Feature;

use App\Enums\Brand;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Subscriber;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_dump_import(): void
    {
        $this->seed(DatabaseSeeder::class);
        $sql = <<<'SQL'
INSERT INTO `agenda` (`id`, `date`, `lieu`, `contenu`, `titre`, `compositeurs`, `special`, `heure`, `jour`) VALUES
(1, '2012-01-08', 'Mamac', 'Cécile Leleux, Mezzo - Fabienne Crutzen, Piano<br />\r\n\r\n', '« Au moulin de la gaieté »', 'Yvain, Offenbach, …', 0, '', ''),
(106, '2020-11-15', 'Musée de la Vie wallonne', '<b>ATTENTION, le concert est donc annulé!</b><br />\r\n<br />\r\nDUO - QUATRE MAINS', '« Occident-Orient » ', 'Ravel', 0, '11h', 'dimanche'),
(155, '2024-10-27', 'palais Provincial', '<h4><a href=\"https://www.provincedeliege.be/votrepalais\">CONCERTS « Votre Palais »</a></H4>\r\n\r\nUne nouvelle dynamique et une nouvelle offre culturelle en vue de rendre le Palais provincial aux Liégeois, de l\'ouvrir à de nouveaux public et de promouvoir les artistes.<br />\r\n<br />\r\n\r\n<b>Duo Sublima</b><br />\r\n<br /> \r\nCécile Leleux: mezzo', '« Mélodies françaises »', 'Entrée gratuite avec la visite de \"Votre Palais\"<br />\r\nTous les concerts sont suivis d’un bar', 1, '15h', 'dimanche'),
(124, '2000-03-13', 'Villa Consulaire', 'Trio', '', '', 0, '11h', 'dimanche');
INSERT INTO `newsletter` (`id`, `email`) VALUES
(1, 'A@b.be'), (2, 'a@b.be'), (3, 'pas-une-adresse');
SQL;
        $agenda = tempnam(sys_get_temp_dir(), 'agenda');
        file_put_contents($agenda, $sql);

        $this->artisan('acdm:import-legacy', ['agenda' => $agenda, '--newsletter' => $agenda])->assertSuccessful();
        // Relancer ne duplique rien.
        $this->artisan('acdm:import-legacy', ['agenda' => $agenda, '--newsletter' => $agenda])->assertSuccessful();

        $this->assertSame(4, Event::count());
        $this->assertSame(1, Subscriber::count());

        $palais = Event::where('legacy_id', 155)->first();
        $this->assertSame(Brand::FoliaMusica, $palais->brand);
        $this->assertSame('votre-palais', $palais->series->slug);
        $this->assertSame('Duo Sublima', $palais->ensemble);
        $this->assertStringNotContainsString('nouvelle dynamique', (string) $palais->performers);
        $this->assertSame('15:00', $palais->starts_at->format('H:i'));
        $this->assertNotNull($palais->price_info);

        $this->assertSame(EventStatus::Cancelled, Event::where('legacy_id', 106)->first()->status);
        $this->assertSame('Au moulin de la gaieté', Event::where('legacy_id', 1)->first()->title);
        $this->assertFalse(Event::where('legacy_id', 124)->first()->is_published);
    }
}
