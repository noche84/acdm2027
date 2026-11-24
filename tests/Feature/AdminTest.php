<?php

namespace Tests\Feature;

use App\Enums\Brand;
use App\Enums\UserRole;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Newsletters\Pages\EditNewsletter;
use App\Filament\Resources\Users\UserResource;
use App\Models\Event;
use App\Models\Newsletter;
use App\Models\Series;
use App\Models\User;
use App\Models\Venue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->editor = User::create(['name' => 'Cécile', 'email' => 'cecile@test.be', 'password' => 'un-long-mot-de-passe', 'role' => UserRole::Editor]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_inactive_user_cannot_enter(): void
    {
        $this->editor->update(['is_active' => false]);
        $this->actingAs($this->editor)->get('/admin')->assertForbidden();
    }

    public function test_editor_creates_a_concert_with_word_content(): void
    {
        $this->actingAs($this->editor);
        $series = Series::where('slug', 'concerts-du-dimanche-matin')->first();

        Livewire::test(CreateEvent::class)
            ->fillForm([
                'brand' => Brand::Acdm->value,
                'series_id' => $series->id,
                'starts_at' => '2027-03-14 11:00:00',
                'venue_id' => Venue::where('name', 'Musée de la Boverie')->value('id'),
                'title' => '« Le clavier s’envole »',
                'performers' => '<p class="MsoNormal"><b>Cansu Sanlidag</b>, piano<o:p></o:p></p>',
                'program' => '<p>Scharwenka &amp; Schubert</p>',
                'status' => 'scheduled',
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $event = Event::latest('id')->first();
        $this->assertSame('Le clavier s’envole', trim($event->title, '« »'));
        $this->assertStringNotContainsString('Mso', $event->performers);
        $this->assertSame($this->editor->id, $event->created_by);
        $this->get($event->url)->assertSee('Cansu Sanlidag');
    }

    public function test_list_and_edit_pages_load(): void
    {
        $this->actingAs($this->editor);
        $event = Event::create(['brand' => Brand::FoliaMusica, 'venue_id' => Venue::first()->id, 'starts_at' => now()->addMonth(), 'title' => 'Les Quatre Saisons']);

        Livewire::test(ListEvents::class)->assertCanSeeTableRecords([$event]);
        Livewire::test(EditEvent::class, ['record' => $event->id])
            ->assertFormSet(['title' => 'Les Quatre Saisons'])
            ->fillForm(['status' => 'cancelled', 'status_note' => 'Report en mai'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('cancelled', $event->fresh()->status->value);
    }

    public function test_editor_cannot_manage_users(): void
    {
        $this->actingAs($this->editor);
        $this->assertFalse(UserResource::canAccess());
        $this->get(UserResource::getUrl('index'))->assertForbidden();
    }

    public function test_newsletter_test_send_action(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->actingAs($this->editor);
        $newsletter = Newsletter::create(['subject' => 'Programme', 'body' => '<p>Bonjour</p>', 'include_upcoming' => true, 'upcoming_count' => 4]);

        Livewire::test(EditNewsletter::class, ['record' => $newsletter->id])
            ->callAction('test', ['email' => 'test@acdm.be'])
            ->assertHasNoActionErrors();

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\NewsletterMail::class, fn ($m) => $m->hasTo('test@acdm.be'));
    }
}
