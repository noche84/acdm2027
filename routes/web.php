<?php

use App\Http\Controllers\InstallController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// Installation sans SSH (OVH Perso) : active seulement si INSTALL_TOKEN est rempli dans .env.
// Sans session ni cache : les tables n'existent pas encore au premier passage.
Route::withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
])->group(function () {
    Route::get('/installation', [InstallController::class, 'show'])->name('install');
    Route::post('/installation', [InstallController::class, 'run'])->name('install.run');
});

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/agenda', [SiteController::class, 'agenda'])->name('agenda');
Route::get('/foliamusica', [SiteController::class, 'foliamusica'])->name('foliamusica');
Route::get('/historique', [SiteController::class, 'archive'])->name('archive');
Route::get('/concerts/{event}', [SiteController::class, 'event'])->name('concerts.show');
Route::get('/concerts/{event}/agenda.ics', [SiteController::class, 'ics'])->name('concerts.ics');
Route::get('/partenaires', [SiteController::class, 'partners'])->name('partners');
Route::get('/photos', fn () => redirect()->away(config('acdm.gallery_url')))->name('photos');
// Images Instagram d'un concert (bouton « Image Instagram » de l'administration).
Route::get('/admin/outils/instagram/{event:id}/{format}.png', [\App\Http\Controllers\InstagramController::class, 'image'])
    ->whereIn('format', ['post', 'story'])
    ->name('admin.instagram');

Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SiteController::class, 'robots'])->name('robots');
Route::get('/agenda.ics', [SiteController::class, 'calendar'])->name('calendar');
Route::get('/artistes', [SiteController::class, 'artists'])->name('artists');

Route::prefix('newsletter')->name('newsletter.')->group(function () {
    Route::get('/', [NewsletterController::class, 'form'])->name('form');
    Route::post('/', [NewsletterController::class, 'subscribe'])->middleware('throttle:5,1')->name('subscribe');
    Route::get('/confirmer/{token}', [NewsletterController::class, 'confirm'])->name('confirm');
    Route::get('/desinscription/{token}', [NewsletterController::class, 'unsubscribeForm'])->name('unsubscribe');
    Route::post('/desinscription/{token}', [NewsletterController::class, 'unsubscribe'])->name('unsubscribe.do');
    // Désinscription « en un clic » demandée par Gmail / Outlook (en-tête List-Unsubscribe-Post).
    Route::post('/desinscription/{token}/un-clic', [NewsletterController::class, 'oneClick'])->name('unsubscribe.oneclick');
});

// Pages éditables (infos pratiques, contact, mentions légales…) — toujours en dernier.
Route::get('/{page:slug}', [SiteController::class, 'page'])
    ->where('page', '[a-z0-9-]+')
    ->name('page');
