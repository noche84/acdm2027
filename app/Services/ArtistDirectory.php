<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Annuaire des musiciens et formations qui se sont produits à l'ACDM / FoliaMusica,
 * reconstitué à partir des concerts. Mis en cache, vidé à chaque modification d'un concert.
 */
class ArtistDirectory
{
    public const CACHE_KEY = 'acdm.artists';

    /** @return array{people: Collection, ensembles: Collection} */
    public function all(): array
    {
        // Le cache ne contient que des tableaux simples (Laravel 13 refuse d'y désérialiser des objets).
        $data = Cache::remember(self::CACHE_KEY, now()->addDay(), fn () => $this->build());

        return ['people' => collect($data['people']), 'ensembles' => collect($data['ensembles'])];
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Un nom de formation, pas une phrase de présentation (« Fêtons la musique », « Hommage à… »). */
    private static function isEnsembleName(string $name): bool
    {
        $name = trim($name, " «»\"“”");
        if (preg_match('/^(duo|trio|quatuor|ensemble|quintette|orchestre)$/iu', $name)) {
            return false;
        }
        if (preg_match('/\b(duo|trio|quatuor|quartet+|quartetto|quintet+e?|sextuor|octuor|ensemble|orchestr\w*|collectif|consort|squad|guitares|ch(œ|oe)ur|band|jazz)\b/iu', $name)) {
            return true;
        }
        // Thèmes de concert (« Le Brésil », « Récital Talent »), instruments seuls (« Piano »).
        if (preg_match('/^(le|la|les|l\'|l’|solo|r[ée]cital|piano|percussions?|violon|orgue|guitare)\b/iu', $name)) {
            return false;
        }
        // Sinon : nom propre dont chaque mot commence par une majuscule (« Alba Novella », « Grupo 46 »).
        return collect(preg_split('/\s+/u', $name))->every(fn ($w) => preg_match('/^[\p{Lu}\d]/u', $w));
    }

    /** « DUO SUBLIMA » → « Duo Sublima » ; garde les noms déjà correctement écrits. */
    private static function tidy(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        $letters = preg_replace('/[^\p{L}]/u', '', $name);
        if ($letters !== '' && mb_strtoupper($letters) === $letters) {
            $name = mb_convert_case(mb_strtolower($name), MB_CASE_TITLE);
            $name = preg_replace_callback('/\b(De|Du|Des|La|Le|Les|Et|À|Of|The|D’|D\'|L’|L\')\b/u', fn ($m) => mb_strtolower($m[1]), $name);
            $name = mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1);
        }

        return $name;
    }

    /** @return array{people: array, ensembles: array} */
    private function build(): array
    {
        $people = [];
        $ensembles = [];

        Event::query()->published()->orderBy('starts_at')->get()->each(function (Event $event) use (&$people, &$ensembles) {
            foreach ($event->performerList() as $person) {
                $key = Str::lower(Str::ascii($person['name']));
                $people[$key] ??= ['name' => $person['name'], 'roles' => [], 'count' => 0, 'last' => null, 'upcoming' => false];
                $people[$key]['count']++;
                $people[$key]['last'] = $event->starts_at->year;
                $people[$key]['upcoming'] = $people[$key]['upcoming'] || ! $event->is_past;
                if ($person['role']) {
                    $people[$key]['roles'][Str::lower($person['role'])] = $person['role'];
                }
            }

            if ($event->ensemble && self::isEnsembleName($event->ensemble)) {
                $name = self::tidy($event->ensemble);
                // « Trio « Jenlis » » et « Trio Jenlis », « l’Ensemble A3 » et « Ensemble A3 » : même formation.
                $key = Str::lower(Str::ascii(preg_replace('/^(l\'|l’|le |la |les )/iu', '', str_replace(['«', '»', '"', '“', '”'], '', $name))));
                $key = preg_replace('/\s+/', ' ', trim($key));
                $ensembles[$key] ??= ['name' => $name, 'count' => 0, 'last' => null, 'upcoming' => false];
                $ensembles[$key]['count']++;
                $ensembles[$key]['last'] = $event->starts_at->year;
                $ensembles[$key]['upcoming'] = $ensembles[$key]['upcoming'] || ! $event->is_past;
            }
        });

        $lastName = fn (array $p) => Str::ascii(Str::lower(Str::afterLast($p['name'], ' ')));

        return [
            'people' => collect($people)->map(fn ($p) => $p + ['roles_label' => implode(', ', array_slice($p['roles'], 0, 3))])->sortBy($lastName)->values()->all(),
            'ensembles' => collect($ensembles)->sortBy(fn ($e) => Str::ascii(Str::lower($e['name'])))->values()->all(),
        ];
    }
}
