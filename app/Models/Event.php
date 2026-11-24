<?php

namespace App\Models;

use App\Enums\Brand;
use App\Enums\EventStatus;
use App\Support\HtmlCleaner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Un concert. Il apparaît dans l'agenda jusqu'à la fin de son jour,
 * puis bascule automatiquement dans l'historique.
 *
 * @property Carbon $starts_at
 * @property Brand $brand
 * @property EventStatus $status
 */
class Event extends Model
{
    protected $fillable = [
        'brand', 'series_id', 'venue_id', 'starts_at', 'slug',
        'title', 'ensemble', 'performers', 'program', 'description',
        'price_info', 'booking_url', 'video_url', 'partner_mention', 'poster',
        'status', 'status_note', 'is_published', 'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'brand' => Brand::class,
            'status' => EventStatus::class,
            'starts_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => \App\Services\ArtistDirectory::flush());
        static::deleted(fn () => \App\Services\ArtistDirectory::flush());

        static::saving(function (Event $event) {
            foreach (['performers', 'program', 'description'] as $field) {
                $event->{$field} = HtmlCleaner::clean($event->{$field});
            }
            foreach (['title', 'ensemble', 'price_info', 'partner_mention', 'status_note'] as $field) {
                $event->{$field} = self::cleanLine($event->{$field});
            }

            if (! $event->slug || $event->isDirty(['starts_at', 'title', 'ensemble'])) {
                $event->slug = $event->makeUniqueSlug();
            }

            if (Auth::check()) {
                $event->updated_by = Auth::id();
                if (! $event->exists) {
                    $event->created_by = Auth::id();
                }
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    // ── Portées ──────────────────────────────────────────────────

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** À venir = jusqu'à la fin de la journée du concert. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<', now()->startOfDay())->orderByDesc('starts_at');
    }

    public function scopeBrand(Builder $query, Brand $brand): Builder
    {
        return $query->where('brand', $brand->value);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            foreach (['title', 'ensemble', 'performers', 'program', 'description'] as $column) {
                $q->orWhere($column, 'like', '%'.$term.'%');
            }
            $q->orWhereHas('venue', fn (Builder $v) => $v->where('name', 'like', '%'.$term.'%'));
        });
    }

    // ── Affichage ────────────────────────────────────────────────

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: ($this->ensemble ?: 'Concert');
    }

    public function getIsPastAttribute(): bool
    {
        return $this->starts_at->lt(now()->startOfDay());
    }

    /** « dimanche 10 janvier 2027 » */
    public function getDateLabelAttribute(): string
    {
        return $this->starts_at->locale('fr')->isoFormat('dddd D MMMM YYYY');
    }

    /** « 11h », « 19h30 » */
    public function getTimeLabelAttribute(): string
    {
        return $this->starts_at->minute === 0
            ? $this->starts_at->format('G').'h'
            : $this->starts_at->format('G\hi');
    }

    public function getPosterUrlAttribute(): ?string
    {
        return $this->poster ? Storage::disk('public')->url($this->poster) : null;
    }

    public function getExcerptAttribute(): string
    {
        return HtmlCleaner::toText($this->program ?: $this->performers, 160);
    }

    public function getUrlAttribute(): string
    {
        return route('concerts.show', $this);
    }

    /**
     * Reconnaît une vidéo YouTube ou Vimeo à partir de n'importe quel lien copié dans le navigateur
     * (youtube.com/watch?v=…, youtu.be/…, /shorts/…, /live/…, /embed/…, vimeo.com/123…).
     *
     * @return array{provider: string, id: string, embed: string, watch: string}|null
     */
    public static function parseVideo(?string $url): ?array
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m)) {
            return [
                'provider' => 'YouTube',
                'id' => $m[1],
                // Domaine « sans cookie » de YouTube, et chargement seulement après un clic (voir la fiche).
                'embed' => 'https://www.youtube-nocookie.com/embed/'.$m[1].'?autoplay=1&rel=0',
                'watch' => 'https://www.youtube.com/watch?v='.$m[1],
            ];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d{6,})~i', $url, $m)) {
            return [
                'provider' => 'Vimeo',
                'id' => $m[1],
                'embed' => 'https://player.vimeo.com/video/'.$m[1].'?autoplay=1&dnt=1',
                'watch' => 'https://vimeo.com/'.$m[1],
            ];
        }

        return null;
    }

    /** @return array{provider: string, id: string, embed: string, watch: string}|null */
    public function getVideoAttribute(): ?array
    {
        return self::parseVideo($this->video_url);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Interprètes reconnus dans le texte libre : « Pierre Vopat, violon », « Cécile Leleux : mezzo »…
     *
     * @return array<int, array{name: string, role: ?string}>
     */
    public function performerList(): array
    {
        $lines = preg_split('#<br\s*/?>|</p>|</li>|\n#i', (string) $this->performers);
        // « Cécile Leleux, mezzo - Fabienne Crutzen, piano » : plusieurs personnes sur une ligne.
        $lines = collect($lines)->flatMap(fn ($l) => preg_match('/,.+\s[-–]\s.+,/u', strip_tags($l)) ? preg_split('/\s[-–]\s/u', strip_tags($l)) : [$l])->all();
        $people = [];

        foreach ($lines as $line) {
            $text = trim(html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B-–:,.");
            if ($text === '' || mb_strlen($text) > 90) {
                continue;
            }
            // « Nom, instrument » / « Nom : instrument » / « Nom – instrument »
            if (preg_match('/^(.{3,50}?)\s*(?:,|:|\s[-–]\s)\s*(.{2,40})$/u', $text, $m)) {
                [$name, $role] = [trim($m[1]), trim($m[2])];
                // « Violon : Gleb Gladkov » → on inverse
                if (preg_match('/^(violon|alto|violoncelle|piano|clavecin|orgue|fl[uû]te|clarinette|hautbois|basson|cor|trompette|trombone|tuba|guitare|harpe|soprano|mezzo|alto|t[ée]nor|baryton|basse|contrebasse|percussions?|accord[ée]on|mandoline|saxophone|chant|direction)/iu', $name)) {
                    [$name, $role] = [$role, $name];
                }
            } else {
                [$name, $role] = [$text, null];
            }

            // « Direction Thomas Van Haeperen » → rôle « direction »
            if (preg_match('/^(direction|chef|piano|violon)\s+(\p{Lu}.+)$/iu', $name, $m) && ! $role) {
                [$name, $role] = [$m[2], $m[1]];
            }
            // « Magali Rischette à la guitare »
            if (preg_match('/^(.+?)\s+(?:à la|au|aux|à l\')\s+(.+)$/u', $name, $m)) {
                [$name, $role] = [$m[1], $role ?: $m[2]];
            }
            // Rôle qui déborde sur le musicien suivant : « violon & Fabrice Renard : piano »
            if ($role) {
                $role = trim(preg_split('/\s(?:&|–)\s|:/u', $role)[0]);
            }

            // « Claire Bourdet & Margaret Hermant » → deux personnes
            foreach (preg_split('/\s+(?:&|et)\s+/u', $name) as $single) {
                $single = self::normalizeName($single);
                $words = preg_split('/\s+/u', $single);
                $looksLikeName = count($words) >= 2 && count($words) <= 5
                    && preg_match('/^\p{Lu}/u', $words[0])
                    && ! preg_match('/[\d:–]|^(duo|trio|quatuor|quartet|quintette|quintet|sextuor|octuor|ensemble|orchestre|collectif|chœur|choeur|costumes|quatre|les|le|la|l\'|l’)\b|\bensemble\b|quatre mains|concert|programme|entrée|réserv|avec|musique|œuvres|oeuvres|pupitre/iu', $single);

                if ($looksLikeName) {
                    $people[] = ['name' => $single, 'role' => $role ? mb_strtolower($role) : null];
                }
            }
        }

        return array_values(array_unique($people, SORT_REGULAR));
    }

    /** « Françoise HOUSEN » → « Françoise Housen », espaces normalisés. */
    private static function normalizeName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name), " \t.,;");

        return preg_replace_callback('/\b(\p{Lu}[\p{Lu}\'’-]+)\b/u', fn ($m) => mb_convert_case(mb_strtolower($m[1]), MB_CASE_TITLE), $name);
    }

    /** Tarif normal, en euros, quand on peut le déduire (pour Google). */
    public function priceValue(): ?float
    {
        if ($this->price_info === null) {
            return $this->brand === Brand::Acdm ? 15.0 : null;
        }
        if (preg_match('/gratuit/iu', $this->price_info)) {
            return 0.0;
        }
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*€/u', $this->price_info, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        return null;
    }

    /** Titre pour Google et les réseaux sociaux. */
    public function getSeoTitleAttribute(): string
    {
        $parts = array_filter([
            $this->title ? '« '.$this->title.' »' : null,
            $this->ensemble,
        ]) ?: [$this->display_title];

        return implode(' – ', $parts).' · Concert classique à Liège, '.$this->starts_at->locale('fr')->isoFormat('D MMMM YYYY');
    }

    public function getSeoDescriptionAttribute(): string
    {
        $who = collect($this->performerList())->take(4)->map(fn ($p) => $p['name'].($p['role'] ? " ({$p['role']})" : ''))->implode(', ');
        $what = rtrim(HtmlCleaner::toText($this->program, 90), ' .…,;');

        return \Illuminate\Support\Str::limit(trim(
            ucfirst($this->date_label).' à '.$this->time_label.', '.($this->venue?->label ?? 'Liège').'. '
            .($who ? $who.'. ' : '')
            .($what ? $what.'. ' : '')
            .($this->brand === Brand::Acdm ? 'Les Concerts du Dimanche Matin, musique classique à Liège.' : 'FoliaMusica, concerts classiques à Liège.')
        ), 158);
    }

    private function makeUniqueSlug(): string
    {
        $base = ($this->starts_at ? Carbon::parse($this->starts_at)->format('Y-m-d') : 'concert')
            .'-'.Str::limit(Str::slug($this->title ?: $this->ensemble ?: 'concert'), 60, '');
        $base = rtrim($base, '-');

        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private static function cleanLine(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $value === '' ? null : $value;
    }
}
