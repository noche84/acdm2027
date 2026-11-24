@extends('layouts.site', ['title' => 'FoliaMusica : concerts classiques exceptionnels à Liège', 'description' => $page?->meta_description, 'bodyClass' => 'brand-foliamusica'])

@section('content')
<header class="page-head brand-foliamusica">
    <div class="wrap page-head-folia">
        <div>
            <p class="kicker">FoliaMusica</p>
            <h1>Des concerts, là où la musique nous emmène</h1>
            <p class="lead">Au Palais provincial, dans les églises de Liège et ailleurs : des rendez-vous exceptionnels, en dehors des dimanches de l'ACDM.</p>
        </div>
        <img src="{{ asset('images/logo-foliamusica.png') }}" alt="" class="folia-logo-big" width="320">
    </div>
</header>

<section class="wrap section">
    <h2 class="section-title">Prochains concerts</h2>
    @if ($events->isEmpty())
        <p class="empty">Aucun concert FoliaMusica annoncé pour l'instant. <a href="{{ route('newsletter.form') }}">Inscrivez-vous à la newsletter</a> pour être prévenu.</p>
    @else
        <div class="event-list">
            @foreach ($events as $event)
                @include('site.partials.event-card', ['event' => $event])
            @endforeach
        </div>
    @endif
</section>

@if ($page?->content)
<section class="section section-alt">
    <div class="wrap prose narrow">
        {!! $page->content !!}
    </div>
</section>
@endif

<section class="wrap section">
    <p class="crosslink brand-acdm">Les concerts du 2<sup>e</sup> dimanche du mois sont dans l'<a href="{{ route('agenda') }}">agenda de l'ACDM</a>.</p>
</section>
@endsection
