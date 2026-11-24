@extends('layouts.site', ['title' => 'Agenda des concerts classiques du dimanche matin à Liège', 'description' => 'Les prochains Concerts du Dimanche Matin : chaque 2e dimanche du mois à 11h, auditorium du musée de la Boverie, Liège.', 'bodyClass' => 'brand-acdm'])

@section('content')
<header class="page-head brand-acdm">
    <div class="wrap">
        <p class="kicker">ACDM</p>
        <h1>Les Concerts du Dimanche Matin</h1>
        <p class="lead">Chaque 2<sup>e</sup> dimanche du mois (sauf juillet et août) à 11h, à l'auditorium du musée de la Boverie.</p>
    </div>
</header>

<section class="wrap section">
    @if ($events->isEmpty())
        <p class="empty">Le programme de la prochaine saison sera bientôt publié. <a href="{{ route('newsletter.form') }}">Inscrivez-vous à la newsletter</a> pour être prévenu.</p>
    @else
        @foreach ($events->groupBy(fn ($e) => $e->starts_at->year) as $year => $items)
            <h2 class="year-title">Saison {{ $year }}</h2>
            <div class="event-list">
                @foreach ($items as $event)
                    @include('site.partials.event-card', ['event' => $event])
                @endforeach
            </div>
        @endforeach
    @endif
</section>

@if ($practical)
<section class="section section-alt">
    <div class="wrap prose narrow">
        <h2>{{ $practical->title }}</h2>
        {!! $practical->content !!}
    </div>
</section>
@endif

<section class="wrap section">
    <p class="crosslink"><a href="{{ route('calendar') }}">Abonnez-vous à l'agenda</a> : les concerts apparaissent automatiquement dans votre calendrier (téléphone, Google Agenda, Outlook).</p>
    <p class="crosslink brand-foliamusica" style="margin-top:1rem">Vous cherchez un concert un autre jour ou ailleurs ? Découvrez les <a href="{{ route('foliamusica') }}">concerts FoliaMusica</a>.</p>
</section>
@endsection
