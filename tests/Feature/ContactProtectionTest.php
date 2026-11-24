<?php

namespace Tests\Feature;

use App\Enums\Brand;
use App\Http\Middleware\ProtectContacts;
use App\Models\Event;
use App\Models\Venue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactProtectionTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETS = [
        'cecile.leleux.be@gmail.com', 'valdescamps@hotmail.com',
        '0499 25 95 30', '+32499259530', '0499259530',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function assertNoClearContact(string $html): void
    {
        foreach (self::SECRETS as $secret) {
            $this->assertStringNotContainsString($secret, $html, "« $secret » apparaît en clair");
        }
    }

    public function test_public_pages_never_show_contacts_in_clear(): void
    {
        $event = Event::create([
            'brand' => Brand::Acdm,
            'venue_id' => Venue::first()->id,
            'starts_at' => now()->addWeek()->setTime(11, 0),
            'title' => 'Récital',
            // Texte tapé par une éditrice, avec coordonnées en clair et dans la description.
            'description' => '<p>Réservations au 0499/25.95.30 ou à cecile.leleux.be@gmail.com.</p>',
        ]);

        foreach (['/', '/contact', '/mentions-legales', '/vie-privee', '/musiciens', '/agenda', $event->url] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertNoClearContact($html);
        }

        $html = $this->get('/contact')->getContent();
        $this->assertStringContainsString('data-cx=', $html);
        $this->assertStringContainsString('data-cx-t=', $html);
        $this->assertStringContainsString('TextDecoder', $html);
    }

    public function test_encoded_values_decode_to_the_original(): void
    {
        $html = $this->get('/contact')->getContent();

        preg_match_all('/data-cx="([^"]+)"/', $html, $links);
        preg_match_all('/data-cx-t="([^"]+)"/', $html, $texts);
        $decoded = array_map([ProtectContacts::class, 'decode'], [...$links[1], ...$texts[1]]);

        $this->assertContains('mailto:cecile.leleux.be@gmail.com', $decoded);
        $this->assertContains('tel:+32499259530', $decoded);
        $this->assertContains('cecile.leleux.be@gmail.com', $decoded);
        $this->assertContains('0499 25 95 30', $decoded);
    }

    public function test_dates_prices_and_share_links_are_left_alone(): void
    {
        $m = new ProtectContacts;
        $html = '<html><head><title>x</title></head><body><p>Concert le 10/01/2027 à 11h, 15 €, 1950–2026, BCE 0452.638.226.</p>'
            .'<a href="mailto:?subject=Concert">E-mail</a></body></html>';

        $this->assertSame($html, $m->protect($html));
    }

    public function test_admin_is_not_rewritten(): void
    {
        $this->get('/admin/login')->assertOk()->assertDontSee('data-cx', false);
    }
}
