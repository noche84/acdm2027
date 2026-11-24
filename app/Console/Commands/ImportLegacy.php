<?php

namespace App\Console\Commands;

use App\Enums\Brand;
use App\Enums\EventStatus;
use App\Enums\SubscriberStatus;
use App\Models\Event;
use App\Models\Series;
use App\Models\Subscriber;
use App\Models\Venue;
use App\Support\HtmlCleaner;
use App\Support\SqlDumpReader;
use Database\Seeders\VenueSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Reprend l'ancienne table `agenda` (2012-2027) et l'ancienne table `newsletter`
 * à partir de leurs exports phpMyAdmin.
 *
 *   php artisan acdm:import-legacy storage/legacy/agenda.sql --newsletter=storage/legacy/newsletter.sql
 *
 * La commande peut être relancée : les concerts sont retrouvés par leur ancien id
 * et mis à jour, rien n'est dupliqué. Un rapport d'anomalies est écrit dans storage/logs.
 */
class ImportLegacy extends Command
{
    protected $signature = 'acdm:import-legacy
        {agenda? : Chemin de l\'export SQL de la table agenda}
        {--newsletter= : Chemin de l\'export SQL de la table newsletter}
        {--from-db : Lire les anciennes tables `agenda` et `newsletter` directement dans la base du site}
        {--dry-run : Analyse seulement, n\'écrit rien en base}';

    protected $description = 'Importe les concerts et les abonnés de l\'ancien site (2012)';

    /** @var array<int, array{id: int|string, date: string, level: string, message: string}> */
    private array $report = [];

    /** @var array<string, Venue> */
    private array $venues = [];

    /** @var array<string, Series> */
    private array $series = [];

    private const VOTRE_PALAIS_INTRO = '/<h4>.*?<\/h4>\s*Une nouvelle dynamique.*?artistes\.(\s*<br\s*\/?>)*/is';

    private const VOTRE_PALAIS_PROGRAM = '/Entrée gratuite avec la visite.*$/is';

    private const LOCATION_PREFIXES = [
        '/^\s*Dans la chapelle d\'hiver.*?Liège\s*(<br\s*\/?>\s*)*/is',
        '/^\s*En l\'église Saint-Nicolas.*?Liège\s*(<br\s*\/?>\s*)*/is',
    ];

    public function handle(): int
    {
        $fromDb = (bool) $this->option('from-db');

        if ($fromDb) {
            if (! Schema::hasTable('agenda')) {
                $this->error('La table `agenda` n\'existe pas dans cette base.');

                return self::FAILURE;
            }
            $rows = DB::table('agenda')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        } elseif ($this->argument('agenda')) {
            $rows = SqlDumpReader::rows($this->argument('agenda'), 'agenda');
        } else {
            $this->error('Indiquez le fichier agenda.sql ou l\'option --from-db.');

            return self::FAILURE;
        }
        $this->info(count($rows).' concerts trouvés.');

        $dry = (bool) $this->option('dry-run');

        DB::beginTransaction();

        try {
            $this->loadReferences();

            $seen = [];
            $bar = $this->output->createProgressBar(count($rows));

            foreach ($rows as $row) {
                $data = $this->transform($row);

                $key = $data['starts_at']->format('Y-m-d').'|'.Str::slug(Str::limit(($data['title'] ?? '').HtmlCleaner::toText($data['performers']), 50, ''));
                if (isset($seen[$key])) {
                    $data['is_published'] = false;
                    $this->note($row, 'erreur', "Doublon probable de l'ancien id {$seen[$key]} (même date, même contenu) : importé mais NON publié.");
                } else {
                    $seen[$key] = $row['id'];
                }

                Event::updateOrCreate(['legacy_id' => (int) $row['id']], $data);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine(2);

            if ($path = $this->option('newsletter')) {
                $this->importSubscribers(SqlDumpReader::rows($path, 'newsletter'));
            } elseif ($fromDb && Schema::hasTable('newsletter')) {
                $this->importSubscribers(DB::table('newsletter')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all());
            }

            $dry ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->writeReport($dry);

        return self::SUCCESS;
    }

    private function loadReferences(): void
    {
        foreach (VenueSeeder::VENUES as $key => $venue) {
            $this->venues[$key] = Venue::firstOrCreate(['name' => $venue['name']], $venue + ['city' => 'Liège']);
        }

        foreach (Series::all() as $series) {
            $this->series[$series->slug] = $series;
        }

        if (! isset($this->series['concerts-du-dimanche-matin'])) {
            throw new \RuntimeException('Les séries n\'existent pas encore : lancez d\'abord « php artisan db:seed ».');
        }
    }

    /** @return array<string, mixed> */
    private function transform(array $row): array
    {
        $contenu = (string) $row['contenu'];
        $titre = (string) $row['titre'];
        $compositeurs = (string) $row['compositeurs'];
        $special = (int) $row['special'] === 1;
        $date = Carbon::parse($row['date']);

        // ── Lieu ─────────────────────────────────────────────
        $venueKey = VenueSeeder::matchLegacy((string) $row['lieu']);
        if ($venueKey) {
            $venue = $this->venues[$venueKey];
        } else {
            $name = Str::ucfirst(trim(strip_tags((string) $row['lieu'])));
            $venue = Venue::firstOrCreate(['name' => $name], ['city' => 'Liège', 'is_active' => false]);
            $this->note($row, 'info', "Lieu inconnu « {$row['lieu']} » : créé tel quel, à compléter dans Lieux.");
        }

        // ── Marque et série ──────────────────────────────────
        $isFolia = str_contains(mb_strtolower($contenu), 'foliamusica')
            || ($special && $date->gte('2024-01-01'));

        $brand = $isFolia ? Brand::FoliaMusica : Brand::Acdm;
        $series = match (true) {
            ! $isFolia && ! $special => $this->series['concerts-du-dimanche-matin'],
            $venueKey === 'palais' => $this->series['votre-palais'] ?? null,
            $venueKey === 'saint-vincent' => $this->series['saint-vincent'] ?? null,
            $isFolia => $this->series['concerts-exceptionnels'] ?? null,
            default => null, // anciens concerts exceptionnels de l'ACDM (Noël, fête de la musique…)
        };

        // ── Nettoyage des textes répétés ──────────────────────
        $priceInfo = null;
        if ($venueKey === 'palais') {
            $contenu = preg_replace(self::VOTRE_PALAIS_INTRO, '', $contenu);
            if (preg_match(self::VOTRE_PALAIS_PROGRAM, $compositeurs)) {
                $compositeurs = preg_replace(self::VOTRE_PALAIS_PROGRAM, '', $compositeurs);
                $priceInfo = $series?->default_price_info;
            }
        }
        foreach (self::LOCATION_PREFIXES as $pattern) {
            $contenu = preg_replace($pattern, '', $contenu);
        }
        if (preg_match('/Entrée gratuite/i', $contenu) && ! $priceInfo) {
            $priceInfo = 'Entrée gratuite';
            $contenu = preg_replace('/(<b>)?\s*Entrée gratuite\s*(<\/b>)?\s*(<br\s*\/?>)?/i', '', $contenu);
        }
        // Liens Google Maps et sites des musées : remplacés par la fiche du lieu.
        foreach (['contenu', 'titre', 'compositeurs'] as $var) {
            $$var = preg_replace('/<a[^>]*(maps|goo\.gl|grandcurtius)[^>]*>.*?<\/a>(\s*<br\s*\/?>)*/is', '', $$var);
        }

        // ── Statut ────────────────────────────────────────────
        $status = EventStatus::Scheduled;
        $statusNote = null;
        if (preg_match('/^\s*(<b>)?\s*(REPORT[ÉE]+[^<]*)(<\/b>)?\s*(<br\s*\/?>\s*)*/iu', $contenu, $m)) {
            $status = EventStatus::Postponed;
            $statusNote = trim(Str::of($m[2])->replace(['REPORTE', 'REPORTÉ', '-'], '')->trim()) ?: null;
            $contenu = substr($contenu, strlen($m[0]));
        } elseif (preg_match('/<b>([^<]*annul[^<]*)<\/b>(\s*<br\s*\/?>)*/iu', $contenu, $m)) {
            $status = EventStatus::Cancelled;
            $statusNote = Str::limit(trim(html_entity_decode($m[1])), 250);
            $contenu = str_replace($m[0], '', $contenu);
        }
        if ($status !== EventStatus::Scheduled) {
            $this->note($row, 'info', 'Statut détecté : '.$status->getLabel().'.');
        }

        // ── Titre (parfois chargé de HTML) ────────────────────
        [$title, $titleRest] = $this->splitTitle($titre);

        // ── Formation (1re ligne courte du contenu) ───────────
        [$ensemble, $performers] = $this->splitEnsemble($contenu);

        $description = trim(($titleRest ?? ''));

        // ── Heure ─────────────────────────────────────────────
        $time = $this->parseTime((string) $row['heure']);
        if (! $time) {
            $time = $special ? '15:00' : '11:00';
            if ($special) {
                $this->note($row, 'avertissement', 'Heure absente : 15h par défaut, à vérifier.');
            }
        }
        $startsAt = Carbon::parse($date->format('Y-m-d').' '.$time);

        // ── Contrôles ─────────────────────────────────────────
        $published = true;
        if ($date->year < 2010 || $date->year > now()->year + 3) {
            $published = false;
            $this->note($row, 'erreur', "Date improbable ({$date->format('d/m/Y')}) : importé mais NON publié, à corriger.");
        }
        $jour = mb_strtolower(trim((string) $row['jour']));
        if ($jour !== '' && $jour !== mb_strtolower($startsAt->locale('fr')->dayName)) {
            $this->note($row, 'avertissement', "L'ancien champ « jour » indique « {$row['jour']} » mais le {$date->format('d/m/Y')} est un {$startsAt->locale('fr')->dayName}.");
        }
        if ($brand === Brand::Acdm && ! $special && $startsAt->gte(now()->startOfDay())) {
            if (! $startsAt->isSunday() || $startsAt->day < 8 || $startsAt->day > 14) {
                $this->note($row, 'avertissement', 'Concert ACDM à venir qui ne tombe pas un 2e dimanche du mois.');
            }
        }
        if (trim(strip_tags($title ?? '')) === '' && ! $ensemble) {
            $this->note($row, 'info', 'Pas de titre : le nom de la formation ou « Concert » sera affiché.');
        }

        return [
            'brand' => $brand,
            'series_id' => $series?->id,
            'venue_id' => $venue->id,
            'starts_at' => $startsAt,
            'title' => $title,
            'ensemble' => $ensemble,
            'performers' => $performers,
            'program' => HtmlCleaner::clean($compositeurs),
            'description' => $description !== '' ? $description : null,
            'price_info' => $priceInfo,
            'status' => $status,
            'status_note' => $statusNote,
            'is_published' => $published,
        ];
    }

    /** @return array{0: ?string, 1: ?string} titre court + reste (HTML) */
    private function splitTitle(string $titre): array
    {
        $parts = preg_split('#<br\s*/?<?>?|</p>|<p[^>]*>|<h[1-6][^>]*>|</h[1-6]>#i', $titre);
        $parts = array_values(array_filter(array_map(fn ($p) => trim(html_entity_decode(strip_tags($p), ENT_QUOTES | ENT_HTML5, 'UTF-8')), $parts), fn ($p) => $p !== ''));

        if (! $parts) {
            return [null, null];
        }

        $title = self::stripQuotes(array_shift($parts));
        $rest = $parts ? HtmlCleaner::clean('<p>'.implode('</p><p>', array_map('e', $parts)).'</p>') : null;

        return [$title !== '' ? Str::limit($title, 250, '…') : null, $rest];
    }

    /**
     * Sépare le nom de la formation (« Trio Memento ») de la liste des interprètes.
     * Règle prudente : un titre en <h3>/<h4>, ou une 1re ligne courte suivie d'une ligne vide,
     * ou une 1re ligne qui commence par Duo/Trio/Quatuor/Ensemble…
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function splitEnsemble(string $contenu): array
    {
        $clean = HtmlCleaner::clean($contenu);
        if (! $clean) {
            return [null, null];
        }

        // <h3>Duo Conventi</h3> en tête
        if (preg_match('#^<h[2-4]>(.*?)</h[2-4]>(?:<br>)*(.*)$#s', $clean, $m)) {
            $name = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($name !== '' && mb_strlen($name) <= 80) {
                return [self::stripQuotes($name), HtmlCleaner::clean($m[2])];
            }
        }

        if (preg_match('#^(.*?)(<br>(?:<br>)?)(.*)$#s', $clean, $m)) {
            $first = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $blankLineAfter = $m[2] === '<br><br>';
            $startsLikeEnsemble = (bool) preg_match('/^(duo|trio|quatuor|quartet|quartetto|quintette|quintet|sextuor|octuor|ensemble|orchestre|collectif|tetracelli|les?\s)/iu', $first);

            $isName = $first !== ''
                && mb_strlen($first) <= 60
                && ! str_contains($first, ',')
                && ! preg_match('/[:;]|\s[-–]\s/u', $first)
                && ! str_contains($m[1], '<h')
                && ($blankLineAfter || $startsLikeEnsemble);

            if ($isName) {
                return [self::stripQuotes($first), HtmlCleaner::clean($m[3])];
            }
        }

        return [null, $clean];
    }

    private function parseTime(string $heure): ?string
    {
        if (preg_match('/(\d{1,2})\s*[hH:]?\s*(\d{2})?/', $heure, $m)) {
            $h = (int) $m[1];
            $min = isset($m[2]) ? (int) $m[2] : 0;
            if ($h >= 0 && $h < 24 && $min < 60) {
                return sprintf('%02d:%02d', $h, $min);
            }
        }

        return null;
    }

    /** Retire les guillemets qui entourent tout le texte (« … », "…"), pas ceux du milieu. */
    private static function stripQuotes(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        if (preg_match('/^[«"“„]\s*(.*?)\s*[»"”]$/u', $text, $m) && ! preg_match('/[«»"“”„]/u', $m[1])) {
            return $m[1];
        }

        return $text;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function importSubscribers(array $rows): void
    {
        $created = $duplicates = $invalid = 0;
        $seen = [];

        foreach ($rows as $row) {
            $email = Str::lower(trim((string) $row['email']));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid++;
                $this->report[] = ['id' => 'newsletter #'.$row['id'], 'date' => '', 'level' => 'avertissement', 'message' => "Adresse invalide ignorée : {$email}"];

                continue;
            }
            if (isset($seen[$email])) {
                $duplicates++;

                continue;
            }
            $seen[$email] = true;

            $subscriber = Subscriber::firstOrNew(['email' => $email]);
            if (! $subscriber->exists) {
                $subscriber->fill([
                    'status' => SubscriberStatus::Confirmed,
                    'source' => 'legacy',
                    'confirmed_at' => now(),
                ])->save();
                $created++;
            }
        }

        $this->info("Abonnés : {$created} importés, {$duplicates} doublons ignorés, {$invalid} adresses invalides.");
        $this->warn('Pensez à importer la liste des désinscrits de MailChimp : php artisan acdm:import-subscribers export.csv --status=unsubscribed');
    }

    private function note(array $row, string $level, string $message): void
    {
        $this->report[] = ['id' => $row['id'], 'date' => (string) $row['date'], 'level' => $level, 'message' => $message];
    }

    private function writeReport(bool $dry): void
    {
        $order = ['erreur' => 0, 'avertissement' => 1, 'info' => 2];
        usort($this->report, fn ($a, $b) => [$order[$a['level']], $a['date']] <=> [$order[$b['level']], $b['date']]);

        $lines = ['# Rapport d\'import de l\'ancien agenda', '', 'Généré le '.now()->format('d/m/Y H:i').($dry ? ' (simulation, rien n\'a été écrit)' : ''), ''];
        $lines[] = '| Ancien id | Date | Niveau | Remarque |';
        $lines[] = '|---|---|---|---|';
        foreach ($this->report as $r) {
            $lines[] = "| {$r['id']} | {$r['date']} | {$r['level']} | ".str_replace('|', '/', $r['message']).' |';
        }

        $file = storage_path('logs/import-legacy-'.now()->format('Ymd-His').'.md');
        File::put($file, implode("\n", $lines)."\n");

        $counts = collect($this->report)->countBy('level');
        $this->table(['Niveau', 'Nombre'], $counts->map(fn ($n, $level) => [$level, $n])->values()->all());
        $this->table(['Ancien id', 'Date', 'Remarque'], collect($this->report)
            ->whereIn('level', ['erreur', 'avertissement'])
            ->map(fn ($r) => [$r['id'], $r['date'], Str::limit($r['message'], 110)])
            ->all());
        $this->info("Rapport complet : {$file}");
    }
}
