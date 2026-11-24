<?php

namespace Tests\Feature;

use App\Enums\Brand;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Venue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function event(array $attrs = []): Event
    {
        return Event::create($attrs + [
            'brand' => Brand::Acdm,
            'venue_id' => Venue::first()->id,
            'starts_at' => now()->addWeek()->setTime(11, 0),
            'title' => 'Love Story',
            'ensemble' => 'Ensemble Emeraude',
            'performers' => '<p>Ales Ulrich, violon</p>',
            'program' => '<p>Janáček, Beethoven</p>',
        ]);
    }

    public function test_public_pages_respond(): void
    {
        $event = $this->event();

        foreach (['/', '/agenda', '/foliamusica', '/historique', '/partenaires', '/newsletter', '/contact', '/infos-pratiques', '/mentions-legales', '/vie-privee', '/sitemap.xml', $event->url] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_event_moves_from_agenda_to_history_after_its_day(): void
    {
        Carbon::setTestNow('2027-01-10 09:00');
        $this->event(['starts_at' => '2027-01-10 11:00', 'title' => 'Florilège']);

        $this->get('/agenda')->assertSee('Florilège');
        $this->get('/historique?annee=2027')->assertDontSee('Florilège');

        // Toujours dans l'agenda le soir même…
        Carbon::setTestNow('2027-01-10 22:00');
        $this->get('/agenda')->assertSee('Florilège');

        // … et dans l'historique dès le lendemain.
        Carbon::setTestNow('2027-01-11 08:00');
        $this->get('/agenda')->assertDontSee('Florilège');
        $this->get('/historique?annee=2027')->assertSee('Florilège');
        Carbon::setTestNow();
    }

    public function test_brands_are_kept_apart(): void
    {
        $this->event(['title' => 'Concert du dimanche']);
        $this->event(['title' => 'Les Quatre Saisons', 'brand' => Brand::FoliaMusica, 'starts_at' => now()->addDays(10)->setTime(19, 30)]);

        $this->get('/agenda')->assertSee('Concert du dimanche')->assertDontSee('Les Quatre Saisons');
        $this->get('/foliamusica')->assertSee('Les Quatre Saisons')->assertDontSee('Concert du dimanche');
    }

    public function test_unpublished_events_are_hidden(): void
    {
        $event = $this->event(['title' => 'Secret', 'is_published' => false]);

        $this->get('/agenda')->assertDontSee('Secret');
        $this->get($event->url)->assertNotFound();
    }

    public function test_cancelled_event_shows_a_banner(): void
    {
        $event = $this->event(['status' => EventStatus::Cancelled, 'status_note' => 'Artiste malade']);

        $this->get($event->url)->assertSee('Annulé')->assertSee('Artiste malade');
    }

    public function test_word_paste_is_cleaned_on_save(): void
    {
        $word = '<p class=MsoNormal style="margin:0"><b><span style="font-family:Calibri">Pierre Vopat</span></b>, violon<o:p></o:p></p>'
            .'<!--[if gte mso 9]><xml>junk</xml><![endif]--><script>alert(1)</script><p class=MsoNormal><span style="font-weight:bold">Álvaro Quintero</span>, violoncelle</p>';

        $event = $this->event(['performers' => $word]);

        $this->assertSame('<p><strong>Pierre Vopat</strong>, violon</p><p><strong>Álvaro Quintero</strong>, violoncelle</p>', $event->performers);
    }

    public function test_legacy_urls_redirect(): void
    {
        $this->get('/?action=Agenda')->assertRedirect(rtrim(config('app.url'), '/').'/agenda')->assertStatus(301);
    }

    public function test_ics_export(): void
    {
        $event = $this->event();

        $this->get(route('concerts.ics', $event))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee('SUMMARY:ACDM – Love Story', false);
    }

    public function test_archive_search(): void
    {
        $this->event(['starts_at' => now()->subYear(), 'title' => 'Hommage à Schubert', 'program' => '<p>Schubert</p>']);
        $this->event(['starts_at' => now()->subYear(), 'title' => 'Tango', 'program' => '<p>Piazzolla</p>']);

        $this->get('/historique?q=Schubert')->assertSee('Hommage à Schubert')->assertDontSee('« Tango »', false);
    }
}
