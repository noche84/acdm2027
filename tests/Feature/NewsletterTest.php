<?php

namespace Tests\Feature;

use App\Enums\Brand;
use App\Enums\NewsletterStatus;
use App\Enums\SubscriberStatus;
use App\Mail\ConfirmSubscriptionMail;
use App\Mail\NewsletterMail;
use App\Models\Event;
use App\Models\Newsletter;
use App\Models\Subscriber;
use App\Models\Venue;
use App\Services\NewsletterSender;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['acdm.newsletter.pause_ms' => 0]);
    }

    public function test_double_opt_in_flow(): void
    {
        Mail::fake();

        $this->post('/newsletter', ['email' => 'Melomane@Example.be', 'consent' => '1'])
            ->assertRedirect('/newsletter');

        $subscriber = Subscriber::where('email', 'melomane@example.be')->firstOrFail();
        $this->assertSame(SubscriberStatus::Pending, $subscriber->status);
        $this->assertNotNull($subscriber->consented_at);
        Mail::assertSent(ConfirmSubscriptionMail::class, fn ($m) => $m->hasTo('melomane@example.be'));

        $this->get($subscriber->confirm_url)->assertOk()->assertSee('Inscription confirmée');
        $this->assertSame(SubscriberStatus::Confirmed, $subscriber->fresh()->status);

        $this->post(route('newsletter.unsubscribe.do', $subscriber->token))->assertOk();
        $this->assertSame(SubscriberStatus::Unsubscribed, $subscriber->fresh()->status);
    }

    public function test_consent_is_required(): void
    {
        $this->post('/newsletter', ['email' => 'a@b.be'])->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('subscribers', 0);
    }

    public function test_honeypot_blocks_bots(): void
    {
        Mail::fake();
        $this->post('/newsletter', ['email' => 'bot@spam.com', 'consent' => '1', 'website' => 'http://spam'])->assertRedirect();
        $this->assertDatabaseCount('subscribers', 0);
        Mail::assertNothingSent();
    }

    public function test_one_click_unsubscribe_without_csrf(): void
    {
        $subscriber = Subscriber::create(['email' => 'x@y.be', 'status' => SubscriberStatus::Confirmed]);

        $this->post(route('newsletter.unsubscribe.oneclick', $subscriber->token))->assertOk();
        $this->assertSame(SubscriberStatus::Unsubscribed, $subscriber->fresh()->status);
    }

    public function test_sending_reaches_only_confirmed_and_can_resume(): void
    {
        Mail::fake();
        config(['acdm.newsletter.batch_size' => 2]);

        foreach (['a@x.be', 'b@x.be', 'c@x.be'] as $email) {
            Subscriber::create(['email' => $email, 'status' => SubscriberStatus::Confirmed]);
        }
        Subscriber::create(['email' => 'pending@x.be', 'status' => SubscriberStatus::Pending]);
        Subscriber::create(['email' => 'gone@x.be', 'status' => SubscriberStatus::Unsubscribed]);

        Event::create(['brand' => Brand::Acdm, 'venue_id' => Venue::first()->id, 'starts_at' => now()->addDays(3), 'title' => 'Love Story']);
        $newsletter = Newsletter::create(['subject' => 'Le programme', 'body' => '<p>Chers amis</p>', 'include_upcoming' => true, 'upcoming_count' => 4]);

        $sender = app(NewsletterSender::class);

        $first = $sender->send($newsletter);
        $this->assertSame(['sent' => 2, 'failed' => 0, 'remaining' => 1], $first);
        $this->assertSame(NewsletterStatus::Draft, $newsletter->fresh()->status);

        $second = $sender->send($newsletter->fresh());
        $this->assertSame(['sent' => 1, 'failed' => 0, 'remaining' => 0], $second);
        $this->assertSame(NewsletterStatus::Sent, $newsletter->fresh()->status);
        $this->assertSame(3, $newsletter->fresh()->recipients_count);

        Mail::assertSentCount(3);
        Mail::assertNotSent(NewsletterMail::class, fn ($m) => $m->hasTo('pending@x.be') || $m->hasTo('gone@x.be'));
    }

    public function test_newsletter_email_renders_with_unsubscribe_link(): void
    {
        $subscriber = Subscriber::create(['email' => 'a@x.be', 'status' => SubscriberStatus::Confirmed]);
        Event::create(['brand' => Brand::FoliaMusica, 'venue_id' => Venue::first()->id, 'starts_at' => now()->addDays(3), 'title' => 'Les Quatre Saisons']);
        $newsletter = Newsletter::create(['subject' => 'Test', 'body' => '<p>Bonjour</p>', 'include_upcoming' => true, 'upcoming_count' => 3]);

        $mail = new NewsletterMail($newsletter, $subscriber);
        $html = $mail->render();

        $this->assertStringContainsString('Les Quatre Saisons', $html);
        $this->assertStringContainsString($subscriber->unsubscribe_url, $html);
        $this->assertStringContainsString('List-Unsubscribe', implode(',', array_keys($mail->headers()->text)));
    }
}
