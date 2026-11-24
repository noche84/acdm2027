<!DOCTYPE html>
<html lang="fr-BE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $pageTitle = isset($title) ? $title.' · ACDM Liège' : 'Concerts de musique classique à Liège · Les Amis des Concerts du Dimanche Matin';
        $pageDescription = $description ?? 'Concerts de musique classique et de musique de chambre à Liège depuis 1950 : musiciens professionnels et artistes de renom, chaque 2e dimanche du mois à 11h au musée de la Boverie. Et les concerts FoliaMusica.';
        $shareImage = $ogImage ?? \App\Support\Seo::defaultImage();
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    @isset($noindex)<meta name="robots" content="noindex, follow">@endisset

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#8b1e2d">

    <meta property="og:site_name" content="Les Amis des Concerts du Dimanche Matin">
    <meta property="og:locale" content="fr_BE">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title" content="{{ $title ?? 'Concerts de musique classique à Liège' }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta property="og:image" content="{{ $shareImage }}">
    @unless (isset($ogImage))<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">@endunless
    <meta property="og:image:alt" content="Les Amis des Concerts du Dimanche Matin – musique classique à Liège">
    <meta name="twitter:card" content="summary_large_image">

    <meta name="geo.region" content="BE-WLG">
    <meta name="geo.placename" content="Liège">

    <link rel="alternate" type="text/calendar" title="Agenda des concerts (ACDM et FoliaMusica)" href="{{ route('calendar') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ @filemtime(public_path('css/site.css')) }}">
    {!! \App\Support\Seo::script(array_merge([\App\Support\Seo::organization(), \App\Support\Seo::website()], $schema ?? [])) !!}
    @stack('head')
</head>
<body class="{{ $bodyClass ?? '' }}">
<a class="skip" href="#contenu">Aller au contenu</a>

<header class="site-header">
    <div class="wrap header-inner">
        <a href="{{ route('home') }}" class="brand">
            <img src="{{ asset('images/logo-acdm.svg') }}" alt="ACDM – Les Amis des Concerts du Dimanche Matin, Liège" width="235" height="60">
        </a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="menu" onclick="this.setAttribute('aria-expanded', this.getAttribute('aria-expanded') !== 'true'); document.getElementById('menu').classList.toggle('open')">
            <span></span><span></span><span></span><span class="sr">Menu</span>
        </button>
        <nav id="menu" class="nav" aria-label="Navigation principale">
            @php($nav = [
                ['home', 'Accueil', null],
                ['agenda', 'Agenda ACDM', 'acdm'],
                ['foliamusica', 'FoliaMusica', 'foliamusica'],
                ['archive', 'Historique', null],
                ['photos', 'Photos', null],
                [['page', 'infos-pratiques'], 'Infos pratiques', null],
                ['newsletter.form', 'Newsletter', null],
                [['page', 'contact'], 'Contact', null],
            ])
            @foreach ($nav as [$r, $label, $tone])
                @php($href = route(...(array) $r))
                <a href="{{ $href }}" @class(['nav-link', 'tone-'.$tone => $tone, 'active' => rtrim(url()->current(), '/') === rtrim($href, '/')]) @if ($r === 'photos') target="_blank" rel="noopener" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </div>
</header>

<main id="contenu">
    @yield('content')
</main>

<footer class="site-footer">
    <div class="wrap footer-grid">
        <div>
            <img src="{{ asset('images/logo-acdm-blanc.svg') }}" alt="ACDM" width="200" height="51" loading="lazy" style="margin-bottom:.6rem">
            <p>Chaque 2<sup>e</sup> dimanche du mois à 11h<br>Auditorium du musée de la Boverie<br>Parc de la Boverie 3, 4020 Liège</p>
        </div>
        <div>
            <p class="footer-title">Réservations</p>
            <p><a href="tel:{{ config('acdm.contact.phone_e164') }}">{{ config('acdm.contact.phone') }}</a><br>
            <a href="mailto:{{ config('acdm.contact.email') }}">{{ config('acdm.contact.email') }}</a></p>
            <p><a class="btn btn-small btn-ghost" href="{{ route('newsletter.form') }}">Recevoir la newsletter</a></p>
            @if ($follow = array_filter(['Facebook' => config('acdm.social.facebook'), 'Groupe Facebook' => config('acdm.facebook_group'), 'Instagram' => config('acdm.social.instagram'), 'YouTube' => config('acdm.social.youtube')]))
                <p class="footer-links">Suivez-nous :
                    @foreach ($follow as $label => $url)
                        <a href="{{ $url }}" rel="noopener" target="_blank">{{ $label }}</a>@if (! $loop->last) · @endif
                    @endforeach
                </p>
            @endif
        </div>
        <div>
            <p class="footer-title">L'association</p>
            <p>ASBL {{ config('acdm.legal.name') }}<br>{{ config('acdm.legal.street') }}, {{ config('acdm.legal.postal_code') }} {{ config('acdm.legal.city') }}<br>BCE {{ config('acdm.legal.enterprise_number') }}</p>
            <p class="footer-links">
                <a href="{{ route('artists') }}">Ils ont joué chez nous</a> ·
                <a href="{{ route('page', 'musiciens') }}">Musiciens : jouer à l'ACDM</a> ·
                <a href="{{ route('calendar') }}">S'abonner à l'agenda</a><br>
                <a href="{{ route('partners') }}">Partenaires</a> ·
                <a href="{{ route('page', 'mentions-legales') }}">Mentions légales et CGU</a> ·
                <a href="{{ route('page', 'vie-privee') }}">Vie privée</a>
            </p>
        </div>
    </div>
    <div class="wrap footer-bottom">
        <span>Avec le soutien de la Fédération Wallonie-Bruxelles, de la Province de Liège et de la Ville de Liège.</span>
    </div>
</footer>
</body>
</html>
