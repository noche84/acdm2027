@extends('layouts.site', ['description' => $page?->meta_description, 'schema' => $schema])

@section('content')
<section class="hero">
    <div class="wrap hero-inner">
        <div class="hero-text">
            <p class="kicker">Musique de chambre à Liège</p>
            <h1>Chaque 2<sup>e</sup> dimanche du mois, à 11h</h1>
            <p class="lead">Une heure de musique classique, de tous les styles et de tous les siècles, à l'auditorium du musée de la Boverie.</p>
            <p class="hero-actions">
                <a class="btn" href="{{ route('agenda') }}">Voir l'agenda</a>
                <a class="btn btn-ghost" href="{{ route('page', 'infos-pratiques') }}">Tarifs et réservations</a>
            </p>
        </div>
        @if ($next)
            <aside class="next-concert brand-acdm" aria-label="Prochain concert ACDM">
                <p class="next-label">Prochain concert</p>
                <p class="next-date">{{ ucfirst($next->date_label) }} · {{ $next->time_label }}</p>
                <h2><a href="{{ $next->url }}">@if ($next->title)« {{ $next->title }} »@else{{ $next->display_title }}@endif</a></h2>
                @if ($next->title && $next->ensemble)<p class="next-ensemble">{{ $next->ensemble }}</p>@endif
                @if ($next->performers)<div class="next-performers">{!! $next->performers !!}</div>@endif
                @if ($banner = $next->status->publicBanner())<p class="badge badge-status">{{ $banner }}</p>@endif
                <p><a class="link-arrow" href="{{ $next->url }}">Programme et infos</a></p>
            </aside>
        @endif
    </div>
</section>

<section class="wrap section">
    <ul class="pitch">
        <li><strong>{{ $stats['years'] }} ans</strong><span>de concerts du dimanche matin à Liège, depuis 1950</span></li>
        <li><strong>{{ $stats['concerts'] }} concerts</strong><span>depuis 2012, à retrouver dans <a href="{{ route('archive') }}">l'historique</a></span></li>
        <li><strong>{{ $stats['artists'] }} musiciens</strong><span>professionnels invités, <a href="{{ route('artists') }}">découvrez-les</a></span></li>
        <li><strong>1 heure</strong><span>de musique de chambre, au cœur du parc de la Boverie</span></li>
    </ul>
</section>

<section class="wrap section two-cols">
    <div class="prose">
        <h2>{{ $page?->title ?? 'Bienvenue' }}</h2>
        {!! $page?->content !!}
        <p><a class="link-arrow" href="{{ route('archive') }}">Notre histoire et les concerts passés</a></p>
    </div>
    @if ($nextFolia)
        <aside class="folia-teaser brand-foliamusica">
            <img src="{{ asset('images/logo-foliamusica.png') }}" alt="FoliaMusica" class="folia-logo" width="220">
            <p>Des concerts exceptionnels, ailleurs et autrement, sous la direction artistique de Cécile Leleux.</p>
            @include('site.partials.event-card', ['event' => $nextFolia, 'compact' => true])
            <p><a class="link-arrow" href="{{ route('foliamusica') }}">Tous les concerts FoliaMusica</a></p>
        </aside>
    @endif
</section>

@if ($upcoming->count() > 1)
<section class="section section-alt">
    <div class="wrap">
        <h2 class="section-title">À l'affiche</h2>
        <div class="event-list">
            @foreach ($upcoming as $event)
                @include('site.partials.event-card', ['event' => $event])
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="wrap section newsletter-band">
    <div>
        <h2>Ne manquez aucun concert</h2>
        <p>Recevez le programme par e-mail, quelques fois par an. Désinscription en un clic.</p>
    </div>
    <a class="btn" href="{{ route('newsletter.form') }}">S'inscrire à la newsletter</a>
</section>
@endsection
