<?php

namespace App\Services;

use App\Enums\NewsletterStatus;
use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie une newsletter aux abonnés confirmés, un par un et par lots.
 * Chaque envoi est noté : si l'opération est interrompue (limite de temps
 * de l'hébergement, limite SMTP), on peut relancer et seuls les abonnés
 * restants seront servis.
 */
class NewsletterSender
{
    /** @return array{sent: int, failed: int, remaining: int} */
    public function send(Newsletter $newsletter): array
    {
        @set_time_limit(300);

        $batch = max(1, (int) config('acdm.newsletter.batch_size', 150));
        $pause = max(0, (int) config('acdm.newsletter.pause_ms', 250));

        $pending = Subscriber::confirmed()
            ->whereNotIn('id', DB::table('newsletter_deliveries')
                ->where('newsletter_id', $newsletter->id)
                ->whereNotNull('sent_at')
                ->select('subscriber_id'))
            ->orderBy('id')
            ->limit($batch)
            ->get();

        $sent = $failed = 0;

        foreach ($pending as $subscriber) {
            try {
                Mail::to($subscriber->email)->send(new NewsletterMail($newsletter, $subscriber));
                $this->record($newsletter, $subscriber, null);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Newsletter : échec d\'envoi', ['email' => $subscriber->email, 'error' => $e->getMessage()]);
                $this->record($newsletter, $subscriber, mb_substr($e->getMessage(), 0, 500));
                $failed++;
            }

            if ($pause) {
                usleep($pause * 1000);
            }
        }

        $remaining = $this->remaining($newsletter);
        $delivered = DB::table('newsletter_deliveries')->where('newsletter_id', $newsletter->id)->whereNotNull('sent_at')->count();
        $failures = DB::table('newsletter_deliveries')->where('newsletter_id', $newsletter->id)->whereNull('sent_at')->count();

        $newsletter->forceFill([
            'recipients_count' => $delivered,
            'failures_count' => $failures,
            'status' => $remaining === 0 ? NewsletterStatus::Sent : NewsletterStatus::Draft,
            'sent_at' => $remaining === 0 ? now() : $newsletter->sent_at,
            'sent_by' => Auth::id() ?? $newsletter->sent_by,
        ])->save();

        return ['sent' => $sent, 'failed' => $failed, 'remaining' => $remaining];
    }

    public function sendTest(Newsletter $newsletter, string $email): void
    {
        Mail::to($email)->send(new NewsletterMail($newsletter, null, isTest: true));
    }

    public function remaining(Newsletter $newsletter): int
    {
        return Subscriber::confirmed()
            ->whereNotIn('id', DB::table('newsletter_deliveries')
                ->where('newsletter_id', $newsletter->id)
                ->whereNotNull('sent_at')
                ->select('subscriber_id'))
            ->count();
    }

    private function record(Newsletter $newsletter, Subscriber $subscriber, ?string $error): void
    {
        DB::table('newsletter_deliveries')->updateOrInsert(
            ['newsletter_id' => $newsletter->id, 'subscriber_id' => $subscriber->id],
            ['sent_at' => $error ? null : now(), 'error' => $error, 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
