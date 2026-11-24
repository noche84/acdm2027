<?php

namespace Tests\Feature;

use App\Enums\Brand;
use App\Enums\UserRole;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use App\Services\InstagramImage;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InstagramVideoTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->editor = User::create(['name' => 'Valérie', 'email' => 'val@test.be', 'password' => 'un-long-mot-de-passe', 'role' => UserRole::Editor]);
        $this->event = Event::create([
            'brand' => Brand::Acdm,
            'venue_id' => Venue::where('name', 'Musée de la Boverie')->value('id'),
            'starts_at' => '2027-01-10 11:00:00',
            'title' => 'Love Story',
            'ensemble' => 'Duo Sublima',
            'performers' => '<p>Cécile Leleux, mezzo</p><p>Jean Schils, piano</p>',
        ]);
    }

    public function test_video_links_are_recognised(): void
    {
        foreach ([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s',
            'https://youtu.be/dQw4w9WgXcQ?si=abc',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
            'https://m.youtube.com/watch?feature=share&v=dQw4w9WgXcQ',
        ] as $url) {
            $this->assertSame('dQw4w9WgXcQ', Event::parseVideo($url)['id'] ?? null, $url);
        }
        $this->assertSame('Vimeo', Event::parseVideo('https://vimeo.com/76979871')['provider']);
        $this->assertNull(Event::parseVideo('https://www.facebook.com/divacecile'));
        $this->assertNull(Event::parseVideo(''));
    }

    public function test_video_loads_only_after_a_click(): void
    {
        $this->event->update(['video_url' => 'https://youtu.be/dQw4w9WgXcQ']);

        $html = $this->get($this->event->url)->assertOk()->getContent();
        $this->assertStringContainsString('En vidéo', $html);
        $this->assertStringContainsString('youtube-nocookie.com/embed/dQw4w9WgXcQ', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function test_form_refuses_an_unknown_video_link(): void
    {
        $this->actingAs($this->editor);

        Livewire::test(EditEvent::class, ['record' => $this->event->id])
            ->fillForm(['video_url' => 'https://www.facebook.com/divacecile'])
            ->call('save')
            ->assertHasFormErrors(['video_url']);

        Livewire::test(EditEvent::class, ['record' => $this->event->id])
            ->fillForm(['video_url' => 'https://youtu.be/dQw4w9WgXcQ'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $this->event->fresh()->video_url);
    }

    public function test_instagram_images_are_reserved_to_the_team(): void
    {
        $url = route('admin.instagram', ['event' => $this->event->id, 'format' => 'post']);
        $this->get($url)->assertForbidden();

        foreach (InstagramImage::FORMATS as $format => [$w, $h]) {
            $response = $this->actingAs($this->editor)
                ->get(route('admin.instagram', ['event' => $this->event->id, 'format' => $format]).'?telecharger=1')
                ->assertOk()
                ->assertHeader('Content-Type', 'image/png');
            $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
            [$iw, $ih] = getimagesizefromstring($response->getContent());
            $this->assertSame([$w, $h], [$iw, $ih]);
        }
    }

    public function test_instagram_modal_offers_images_and_caption(): void
    {
        $this->actingAs($this->editor);

        Livewire::test(EditEvent::class, ['record' => $this->event->id])
            ->mountAction('instagram')
            ->assertActionMounted('instagram')
            ->assertMountedActionModalSee(['Copier le texte', 'Télécharger', 'Dimanche 10 janvier 2027 à 11h', 'Cécile Leleux (mezzo)']);
    }
}
