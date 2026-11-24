<?php

namespace App\Http\Controllers;

use App\Enums\SubscriberStatus;
use App\Mail\ConfirmSubscriptionMail;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Inscription en double opt-in, conforme au RGPD :
 * 1. le visiteur donne son adresse et coche la case de consentement ;
 * 2. il reçoit un e-mail avec un lien de confirmation ;
 * 3. il n'est abonné qu'après avoir cliqué. Chaque newsletter contient un lien de désinscription.
 */
class NewsletterController extends Controller
{
    public function form(): View
    {
        return view('site.newsletter.form');
    }

    public function subscribe(Request $request): RedirectResponse
    {
        // Champ piège invisible : rempli uniquement par les robots.
        if (filled($request->input('website'))) {
            return redirect()->route('newsletter.form')->with('status', 'check-email');
        }

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'consent' => ['accepted'],
        ], [
            'email.required' => 'Merci d\'indiquer votre adresse e-mail.',
            'email.email' => 'Cette adresse e-mail ne semble pas valide.',
            'consent.accepted' => 'Merci de cocher la case pour accepter de recevoir la newsletter.',
        ]);

        // Ménage RGPD : les inscriptions jamais confirmées sont effacées après 30 jours.
        Subscriber::where('status', SubscriberStatus::Pending->value)->where('created_at', '<', now()->subDays(30))->delete();

        $subscriber = Subscriber::firstOrNew(['email' => strtolower(trim($data['email']))]);

        if ($subscriber->exists && $subscriber->status === SubscriberStatus::Confirmed) {
            // Ne révèle pas si l'adresse est déjà connue.
            return redirect()->route('newsletter.form')->with('status', 'check-email');
        }

        $subscriber->forceFill([
            'status' => SubscriberStatus::Pending,
            'source' => $subscriber->source ?? 'site',
            'consented_at' => now(),
            'consent_ip' => $request->ip(),
            'token' => null, // nouveau jeton à chaque demande
        ])->save();

        try {
            Mail::to($subscriber->email)->send(new ConfirmSubscriptionMail($subscriber));
        } catch (\Throwable $e) {
            Log::error('Newsletter : e-mail de confirmation non envoyé', ['error' => $e->getMessage()]);

            return back()->withInput()->withErrors(['email' => 'L\'e-mail de confirmation n\'a pas pu être envoyé. Réessayez plus tard ou contactez-nous.']);
        }

        return redirect()->route('newsletter.form')->with('status', 'check-email');
    }

    public function confirm(string $token): View
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if ($subscriber && $subscriber->status !== SubscriberStatus::Confirmed) {
            $subscriber->confirm();
        }

        return view('site.newsletter.message', [
            'title' => $subscriber ? 'Inscription confirmée' : 'Lien expiré',
            'message' => $subscriber
                ? 'Merci ! Vous recevrez désormais le programme de nos concerts.'
                : 'Ce lien de confirmation n\'est plus valable. Vous pouvez vous inscrire à nouveau.',
        ]);
    }

    public function unsubscribeForm(string $token): View
    {
        return view('site.newsletter.unsubscribe', [
            'subscriber' => Subscriber::where('token', $token)->first(),
            'token' => $token,
        ]);
    }

    public function unsubscribe(string $token): View
    {
        Subscriber::where('token', $token)->first()?->unsubscribe();

        return view('site.newsletter.message', [
            'title' => 'Désinscription effectuée',
            'message' => 'Vous ne recevrez plus notre newsletter. Vous pouvez vous réinscrire à tout moment.',
        ]);
    }

    public function oneClick(string $token): Response
    {
        Subscriber::where('token', $token)->first()?->unsubscribe();

        return response('', 200);
    }
}
