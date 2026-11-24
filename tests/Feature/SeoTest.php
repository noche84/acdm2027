<?php

namespace Tests\Feature;

use App\Enums\Brand;
use App\Models\Event;
use App\Models\Venue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
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
            'venue_id' => Venue::where('name', 'Musée de la Boverie')->value('id'),
            'starts_at' => now()->addWeek()->setTime(11, 0),
            'title' => 'Love Story',
            'ensemble' => 'Trio Memento',
            'performers' => '<p>Krzysztof Lukasiewicz, piano<br>Valérie Maurice : violon<br>Violoncelle : Élisabeth Deletaille</p>',
            'program' => '<p>Dvořák, Schubert</p>',
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        return collect($m[1])->flatMap(fn ($json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR)['@graph'])->all();
    }

    public function test_event_page_exposes_complete_music_event_data(): void
    {
        $event = $this->event();
        $html = $this->get($event->url)->assertOk()->getContent();

        $nodes = collect($this->jsonLd($html))->keyBy('@type');
        $this->assertTrue($nodes->has('Organization'));
        $this->assertTrue($nodes->has('BreadcrumbList'));

        $music = $nodes['MusicEvent'];
        $this->assertSame('15.00', $music['offers']['price']);
        $this->assertSame('EUR', $music['offers']['priceCurrency']);
        $this->assertSame('4020', $music['location']['address']['postalCode']);
        $this->assertSame(
            ['Trio Memento', 'Krzysztof Lukasiewicz', 'Valérie Maurice', 'Élisabeth Deletaille'],
            array_column($music['performer'], 'name'),
        );
        $this->assertSame('violoncelle', $music['performer'][3]['jobTitle']);

        $this->assertStringContainsString('<meta property="og:image"', $html);
        $this->assertStringContainsString('Concert classique à Liège', $html);
    }

    public function test_free_events_have_a_zero_price(): void
    {
        $event = $this->event(['brand' => Brand::FoliaMusica, 'price_info' => 'Entrée gratuite']);
        $music = collect($this->jsonLd($this->get($event->url)->getContent()))->firstWhere('@type', 'MusicEvent');

        $this->assertSame('0.00', $music['offers']['price']);
    }

    public function test_home_lists_upcoming_events_for_google(): void
    {
        $this->event();
        $types = array_column($this->jsonLd($this->get('/')->getContent()), '@type');

        $this->assertContains('MusicEvent', $types);
        $this->assertContains('WebSite', $types);
    }

    public function test_icons_and_manifest_are_linked(): void
    {
        $html = $this->get('/')->getContent();

        foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png', 'site.webmanifest', 'images/logo-acdm.svg'] as $file) {
            $this->assertStringContainsString($file, $html);
            $this->assertFileExists(public_path($file));
        }
    }

    public function test_robots_blocks_test_copies_and_allows_production(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');

        app()->detectEnvironment(fn () => 'production');
        $this->get('/robots.txt')->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_sitemap_has_lastmod_and_key_pages(): void
    {
        $event = $this->event();

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('<loc>'.route('artists').'</loc>', false)
            ->assertSee('<loc>'.$event->url.'</loc>', false)
            ->assertSee('<lastmod>', false);
    }

    public function test_calendar_feed_lists_concerts(): void
    {
        $this->event(['title' => 'Concert un']);
        $this->event(['title' => 'Concert deux', 'starts_at' => now()->addMonth()]);

        $ics = $this->get('/agenda.ics')->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->getContent();
        $this->assertSame(2, substr_count($ics, 'BEGIN:VEVENT'));
        $this->assertStringContainsString('X-WR-CALNAME', $ics);
    }

    public function test_artists_page_lists_performers_and_ensembles(): void
    {
        $this->event();

        $this->get('/artistes')->assertOk()
            ->assertSee('Trio Memento')
            ->assertSee('Valérie Maurice')
            ->assertSee('Élisabeth Deletaille');
    }

    public function test_artist_directory_is_refreshed_when_a_concert_changes(): void
    {
        $event = $this->event();
        $this->get('/artistes')->assertSee('Trio Memento');

        $event->update(['ensemble' => 'Quatuor Légia']);
        $this->get('/artistes')->assertSee('Quatuor Légia')->assertDontSee('Trio Memento');
    }

    public function test_musicians_page_exists(): void
    {
        $this->get('/musiciens')->assertOk()->assertSee('Proposer un concert');
    }
}
