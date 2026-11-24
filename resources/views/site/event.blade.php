@php
    $titleText = $event->title ? '« '.$event->title.' »' : $event->display_title;
    $section = $event->is_past ? ['Historique', route('archive')] : ($event->brand === \App\Enums\Brand::Acdm ? ['Agenda ACDM', route('agenda')] : ['FoliaMusica', route('foliamusica')]);
@endphp
@extends('layouts.site', [
    'title' => $event->seo_title,
    'description' => $event->seo_description,
    'ogImage' => $event->poster_url,
    'bodyClass' => $event->brand->cssClass(),
    'schema' => [
        \App\Support\Seo::event($event),
        \App\Support\Seo::breadcrumb([['Accueil', route('home')], $section, [$event->display_title, $event->url]]),
    ],
])

@section('content')
<article class="event-page">
    <header class="page-head {{ $event->brand->cssClass() }}">
        <div class="wrap">
            <p class="kicker">
                <a href="{{ route($event->brand->routeName()) }}">{{ $event->brand->getLabel() }}</a>
                @if ($event->series && $event->brand === \App\Enums\Brand::FoliaMusica) · {{ $event->series->name }}@endif
                @if ($event->is_past) · concert passé @endif
            </p>
            <h1>{{ $titleText }}</h1>
            @if ($event->title && $event->ensemble)<p class="lead">{{ $event->ensemble }}</p>@endif
            @if ($banner = $event->status->publicBanner())
                <p class="status-banner">{{ $banner }}@if ($event->status_note) — {{ $event->status_note }}@endif</p>
            @endif
        </div>
    </header>

    <div class="wrap section event-layout">
        <div class="prose">
            @if ($event->performers)
                <h2>Interprètes</h2>
                <div class="performers">{!! $event->performers !!}</div>
            @endif
            @if ($event->program)
                <h2>Programme</h2>
                <div class="program">{!! $event->program !!}</div>
            @endif
            @if ($event->description)
                <div class="description">{!! $event->description !!}</div>
            @endif
            @if ($video = $event->video)
                <h2>En vidéo</h2>
                {{-- La vidéo ne se charge qu'au clic : aucun cookie ni appel à {{ $video['provider'] }} avant. --}}
                <div class="video" data-embed="{{ $video['embed'] }}" @if ($event->poster_url) style="background-image:url('{{ $event->poster_url }}')" @endif>
                    <button type="button" class="video-play" aria-label="Lire la vidéo">
                        <span class="video-icon" aria-hidden="true">▶</span>
                        <span class="video-label">Écouter les musiciens</span>
                    </button>
                    <p class="video-note">Vidéo hébergée par {{ $video['provider'] }} : elle ne se charge que si vous cliquez.
                        <a href="{{ $video['watch'] }}" target="_blank" rel="noopener">Ouvrir sur {{ $video['provider'] }}</a></p>
                </div>
                <script>
                    document.querySelectorAll('.video[data-embed]').forEach(function (box) {
                        box.querySelector('.video-play').addEventListener('click', function () {
                            var f = document.createElement('iframe');
                            f.src = box.getAttribute('data-embed');
                            f.title = 'Vidéo des musiciens';
                            f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
                            f.allowFullscreen = true;
                            box.innerHTML = ''; box.style.backgroundImage = 'none'; box.appendChild(f);
                        });
                    });
                </script>
            @endif
            @if ($event->series?->description)
                <div class="series-note">{!! $event->series->description !!}</div>
            @endif
            @if ($event->poster_url)
                <figure class="poster"><img src="{{ $event->poster_url }}" alt="Affiche du concert {{ $event->display_title }}" loading="lazy"></figure>
            @endif
        </div>

        <aside class="event-aside">
            <dl>
                <dt>Quand</dt>
                <dd>{{ ucfirst($event->date_label) }}<br>à {{ $event->time_label }}</dd>
                @if ($event->venue)
                    <dt>Où</dt>
                    <dd>{{ $event->venue->label }}@if ($event->venue->address)<br>{{ $event->venue->address }}@endif
                        <br><a href="{{ $event->venue->map_link }}" target="_blank" rel="noopener">Voir le plan</a>
                        @if ($event->venue->access_info)<br><small>{{ $event->venue->access_info }}</small>@endif
                    </dd>
                @endif
                <dt>Entrée</dt>
                <dd>
                    @if ($event->price_info)
                        {{ $event->price_info }}
                    @elseif ($event->brand === \App\Enums\Brand::Acdm)
                        <a href="{{ route('page', 'infos-pratiques') }}">Tarifs habituels de l'ACDM</a>
                    @else
                        Renseignements : {{ config('acdm.contact.phone') }}
                    @endif
                </dd>
                @if ($event->partner_mention)
                    <dt>En partenariat</dt>
                    <dd>{{ $event->partner_mention }}</dd>
                @endif
            </dl>
            @unless ($event->is_past)
                @if ($event->status !== \App\Enums\EventStatus::Cancelled)
                    <p class="aside-actions">
                        @if ($event->booking_url)
                            <a class="btn" href="{{ $event->booking_url }}" target="_blank" rel="noopener">Réserver</a>
                        @else
                            <a class="btn" href="tel:{{ config('acdm.contact.phone_e164') }}">Réserver : {{ config('acdm.contact.phone') }}</a>
                            <a class="btn btn-ghost" href="mailto:{{ config('acdm.contact.email') }}?subject={{ rawurlencode('Réservation – '.$event->display_title.' – '.$event->starts_at->format('d/m/Y')) }}">Réserver par e-mail</a>
                        @endif
                        <a class="link-arrow" href="{{ route('concerts.ics', $event) }}">Ajouter à mon agenda</a>
                    </p>
                @endif
            @endunless
            @php($shareText = $event->display_title.' – '.ucfirst($event->date_label).' à '.$event->time_label.', '.($event->venue?->name ?? 'Liège'))
            <p class="share">
                <span>Partager</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($event->url) }}" target="_blank" rel="noopener">Facebook</a>
                <a href="https://wa.me/?text={{ rawurlencode($shareText.' '.$event->url) }}" target="_blank" rel="noopener">WhatsApp</a>
                <a href="mailto:?subject={{ rawurlencode($shareText) }}&body={{ rawurlencode('Je pense que ce concert pourrait te plaire : '.$event->url) }}">E-mail</a>
            </p>
        </aside>
    </div>

    @if ($practical && ! $event->is_past)
    <section class="section section-alt">
        <div class="wrap prose narrow">{!! $practical->content !!}</div>
    </section>
    @endif

    @if ($others->isNotEmpty())
    <section class="wrap section">
        <h2 class="section-title">Prochainement</h2>
        <div class="event-list">
            @foreach ($others as $other)
                @include('site.partials.event-card', ['event' => $other, 'compact' => true])
            @endforeach
        </div>
    </section>
    @endif
</article>
@endsection
