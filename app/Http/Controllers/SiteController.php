<?php

namespace App\Http\Controllers;

use App\Enums\Brand;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Page;
use App\Models\Partner;
use App\Support\HtmlCleaner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SiteController extends Controller
{
    /** Anciennes adresses du site de 2012 (index.php?action=…). */
    private const LEGACY_ACTIONS = [
        'agenda' => '/agenda',
        'historique' => '/historique',
        'photos' => '/photos',
        'newsletter' => '/newsletter',
        'partenaires' => '/partenaires',
        'contact' => '/contact',
        'accueil' => '/',
    ];

    public function home(Request $request): View|RedirectResponse
    {
        if ($action = $request->query('action')) {
            // On repart de APP_URL : l'ancienne adresse contient « index.php ».
            $path = self::LEGACY_ACTIONS[strtolower((string) $action)] ?? '/';

            return redirect()->away(rtrim(config('app.url'), '/').$path, 301);
        }

        $upcoming = Event::query()->published()->upcoming()->with(['venue', 'series'])->get();
        $directory = app(\App\Services\ArtistDirectory::class)->all();

        return view('site.home', [
            'stats' => [
                'years' => now()->year - 1950,
                'concerts' => Event::query()->published()->past()->count(),
                'artists' => $directory['people']->count(),
            ],
            'schema' => $upcoming->take(10)->map(fn ($e) => \App\Support\Seo::event($e))->all(),
            'page' => Page::findSystem('accueil'),
            'next' => $upcoming->firstWhere('brand', Brand::Acdm),
            'nextFolia' => $upcoming->firstWhere('brand', Brand::FoliaMusica),
            'upcoming' => $upcoming->take(6),
        ]);
    }

    public function agenda(): View
    {
        return view('site.agenda', [
            'brand' => Brand::Acdm,
            'events' => Event::query()->published()->upcoming()->brand(Brand::Acdm)->with(['venue', 'series'])->get(),
            'practical' => Page::findSystem('infos-pratiques'),
        ]);
    }

    public function foliamusica(): View
    {
        return view('site.foliamusica', [
            'brand' => Brand::FoliaMusica,
            'page' => Page::findSystem('foliamusica'),
            'events' => Event::query()->published()->upcoming()->brand(Brand::FoliaMusica)->with(['venue', 'series'])->get(),
        ]);
    }

    public function archive(Request $request): View
    {
        $brand = Brand::tryFrom((string) $request->query('nom'));
        $q = trim((string) $request->query('q'));

        $years = Event::query()->published()->past()
            ->selectRaw($this->yearExpression().' as y')
            ->distinct()->orderByDesc('y')->pluck('y')->map(fn ($y) => (int) $y);

        $year = $q === '' ? (int) ($request->query('annee') ?: $years->first()) : null;

        $events = Event::query()->published()->past()
            ->with(['venue', 'series'])
            ->when($brand, fn ($query) => $query->brand($brand))
            ->when($year, fn ($query) => $query->whereBetween('starts_at', ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"]))
            ->search($q)
            ->limit($q === '' ? 200 : 100)
            ->get();

        return view('site.archive', [
            'page' => Page::findSystem('historique'),
            'events' => $events,
            'years' => $years,
            'year' => $year,
            'brand' => $brand,
            'q' => $q,
            'total' => Event::query()->published()->past()->count(),
        ]);
    }

    public function event(Event $event): View
    {
        abort_unless($event->is_published, 404);
        $event->load(['venue', 'series']);

        return view('site.event', [
            'event' => $event,
            'practical' => $event->brand === Brand::Acdm && ! $event->price_info ? Page::findSystem('infos-pratiques') : null,
            'others' => Event::query()->published()->upcoming()->whereKeyNot($event->id)->with('venue')->limit(3)->get(),
        ]);
    }

    public function ics(Event $event): Response
    {
        abort_unless($event->is_published, 404);
        $event->load('venue');

        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//ACDM//Concerts//FR', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', ...$this->vevent($event), 'END:VCALENDAR'];

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$event->slug.'.ics"',
        ]);
    }

    public function partners(): View
    {
        return view('site.partners', [
            'groups' => Partner::query()->active()->get()->groupBy('category'),
        ]);
    }

    public function page(Page $page): View|RedirectResponse
    {
        abort_unless($page->is_published, 404);

        // Ces pages ont leur propre adresse, plus riche.
        $dedicated = ['accueil' => 'home', 'historique' => 'archive', 'foliamusica' => 'foliamusica'];
        if (isset($dedicated[$page->slug])) {
            return redirect()->route($dedicated[$page->slug]);
        }

        return view('site.page', ['page' => $page]);
    }

    public function sitemap(): Response
    {
        $latest = Event::query()->published()->max('updated_at');
        $entries = collect([
            [route('home'), $latest, '1.0', 'weekly'],
            [route('agenda'), $latest, '0.9', 'weekly'],
            [route('foliamusica'), $latest, '0.8', 'weekly'],
            [route('archive'), $latest, '0.6', 'monthly'],
            [route('artists'), $latest, '0.6', 'monthly'],
            [route('partners'), null, '0.3', 'yearly'],
            [route('newsletter.form'), null, '0.4', 'yearly'],
        ])
            ->merge(Page::where('is_published', true)->whereNotIn('slug', ['accueil', 'historique', 'foliamusica'])->get()
                ->map(fn ($p) => [route('page', $p), $p->updated_at, '0.5', 'monthly']))
            ->merge(Event::query()->published()->orderByDesc('starts_at')->get()
                ->map(fn ($e) => [$e->url, $e->updated_at, $e->is_past ? '0.4' : '0.8', $e->is_past ? 'yearly' : 'weekly']));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($entries as [$url, $lastmod, $priority, $freq]) {
            $xml .= '<url><loc>'.e($url).'</loc>'
                .($lastmod ? '<lastmod>'.\Illuminate\Support\Carbon::parse($lastmod)->toAtomString().'</lastmod>' : '')
                ."<changefreq>{$freq}</changefreq><priority>{$priority}</priority></url>";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $lines = app()->environment('production')
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /installation', 'Disallow: /newsletter/confirmer/', 'Disallow: /newsletter/desinscription/', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /']; // pas d'indexation des copies de test

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }

    /** Agenda complet au format iCalendar : abonnement dans Google Agenda, iPhone, Outlook. */
    public function calendar(): Response
    {
        $events = Event::query()->published()->where('starts_at', '>=', now()->subMonths(2))->orderBy('starts_at')->with('venue')->get();

        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//ACDM//Concerts//FR', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'X-WR-CALNAME:Concerts ACDM & FoliaMusica (Liège)', 'X-WR-TIMEZONE:Europe/Brussels', 'REFRESH-INTERVAL;VALUE=DURATION:P1D', 'X-PUBLISHED-TTL:P1D'];
        foreach ($events as $event) {
            array_push($lines, ...$this->vevent($event));
        }
        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="concerts-acdm.ics"',
        ]);
    }

    public function artists(): View
    {
        return view('site.artists', app(\App\Services\ArtistDirectory::class)->all());
    }

    /** @return array<int, string> */
    private function vevent(Event $event): array
    {
        $escape = fn (?string $s) => str_replace(["\\", ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], (string) $s);
        $start = $event->starts_at->copy()->utc();

        return [
            'BEGIN:VEVENT',
            'UID:concert-'.$event->id.'@'.parse_url(url('/'), PHP_URL_HOST),
            'DTSTAMP:'.$event->updated_at?->copy()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$start->copy()->addMinutes(75)->format('Ymd\THis\Z'),
            'SUMMARY:'.$escape($event->brand->getLabel().' – '.$event->display_title),
            'LOCATION:'.$escape(trim(($event->venue?->label ?? '').', '.($event->venue?->address ?? ''), ', ')),
            'DESCRIPTION:'.$escape(HtmlCleaner::toText($event->performers.' '.$event->program, 500)."\n".$event->url),
            'URL:'.$event->url,
            'STATUS:'.($event->status === EventStatus::Cancelled ? 'CANCELLED' : 'CONFIRMED'),
            'END:VEVENT',
        ];
    }

    private function yearExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y', starts_at)" : 'YEAR(starts_at)';
    }
}
