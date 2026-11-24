<?php

namespace App\Support;

use App\Enums\Brand;
use App\Enums\EventStatus;
use App\Models\Event;

/**
 * Données structurées schema.org (JSON-LD) : c'est ce que Google lit pour afficher
 * les concerts dans ses résultats « événements » et le logo de l'association.
 */
class Seo
{
    public static function defaultImage(): string
    {
        return asset('images/partage-acdm.jpg');
    }

    /** @return array<string, mixed> */
    public static function organization(): array
    {
        $legal = config('acdm.legal');

        return [
            '@type' => 'Organization',
            '@id' => url('/').'#organisation',
            'name' => 'Les Amis des Concerts du Dimanche Matin',
            'alternateName' => ['ACDM', 'Concerts du Dimanche Matin', 'FoliaMusica'],
            'legalName' => 'ASBL '.$legal['name'],
            'url' => url('/'),
            'logo' => asset('icon-512.png'),
            'image' => self::defaultImage(),
            'description' => 'Association liégeoise qui organise depuis 1950 des concerts de musique classique et de musique de chambre avec des musiciens professionnels, chaque 2e dimanche du mois à 11h au musée de la Boverie.',
            'foundingDate' => '1950',
            'vatID' => 'BE'.str_replace('.', '', $legal['enterprise_number']),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $legal['street'],
                'postalCode' => $legal['postal_code'],
                'addressLocality' => $legal['city'],
                'addressCountry' => 'BE',
            ],
            'areaServed' => ['@type' => 'City', 'name' => 'Liège'],
            'sameAs' => array_values(array_filter(config('acdm.social', []))),
        ];
    }

    /** @return array<string, mixed> */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/').'#site',
            'url' => url('/'),
            'name' => 'Les Amis des Concerts du Dimanche Matin – Concerts classiques à Liège',
            'inLanguage' => 'fr-BE',
            'publisher' => ['@id' => url('/').'#organisation'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('archive').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function event(Event $event): array
    {
        $venue = $event->venue;
        $price = $event->priceValue();

        $performers = collect($event->performerList())->map(fn ($p) => array_filter([
            '@type' => 'Person',
            'name' => $p['name'],
            'jobTitle' => $p['role'],
        ]))->values()->all();
        if ($event->ensemble) {
            array_unshift($performers, ['@type' => 'MusicGroup', 'name' => $event->ensemble]);
        }

        return array_filter([
            '@type' => 'MusicEvent',
            '@id' => $event->url.'#concert',
            'name' => $event->title ? $event->title.($event->ensemble ? ' – '.$event->ensemble : '') : $event->display_title,
            'description' => $event->seo_description,
            'url' => $event->url,
            'startDate' => $event->starts_at->toIso8601String(),
            'endDate' => $event->starts_at->copy()->addMinutes(75)->toIso8601String(),
            'eventStatus' => match ($event->status) {
                EventStatus::Cancelled => 'https://schema.org/EventCancelled',
                EventStatus::Postponed => 'https://schema.org/EventPostponed',
                default => 'https://schema.org/EventScheduled',
            },
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'inLanguage' => 'fr',
            'image' => [$event->poster_url ?? self::defaultImage()],
            'location' => $venue ? [
                '@type' => 'Place',
                'name' => $venue->label,
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $venue->street,
                    'postalCode' => $venue->postal_code,
                    'addressLocality' => $venue->city ?: 'Liège',
                    'addressRegion' => 'Wallonie',
                    'addressCountry' => 'BE',
                ]),
            ] : null,
            'performer' => $performers ?: null,
            'organizer' => [
                '@type' => 'Organization',
                'name' => $event->brand === Brand::Acdm ? 'Les Amis des Concerts du Dimanche Matin' : 'FoliaMusica',
                'url' => $event->brand === Brand::Acdm ? url('/') : route('foliamusica'),
            ],
            'offers' => $price !== null ? [
                '@type' => 'Offer',
                'price' => number_format($price, 2, '.', ''),
                'priceCurrency' => 'EUR',
                'url' => $event->booking_url ?: $event->url,
                'availability' => match ($event->status) {
                    EventStatus::SoldOut => 'https://schema.org/SoldOut',
                    EventStatus::Cancelled => 'https://schema.org/Discontinued',
                    default => 'https://schema.org/InStock',
                },
                'validFrom' => $event->created_at?->toIso8601String(),
            ] : null,
        ], fn ($v) => $v !== null && $v !== []);
    }

    /** @param  array<int, array{0: string, 1: string}>  $items  [libellé, url] */
    public static function breadcrumb(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item[0],
                'item' => $item[1],
            ])->all(),
        ];
    }

    /** @param  array<int, array<string, mixed>>  $nodes */
    public static function script(array $nodes): string
    {
        $json = json_encode(['@context' => 'https://schema.org', '@graph' => array_values($nodes)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

        return '<script type="application/ld+json">'.$json.'</script>';
    }
}
