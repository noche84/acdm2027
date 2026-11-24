@extends('layouts.site', ['title' => 'Historique des concerts classiques à Liège depuis 1950', 'noindex' => request()->filled('q') ? true : null, 'description' => 'L\'histoire des Concerts du Dimanche Matin et les archives de tous nos concerts depuis 2012.'])

@section('content')
<header class="page-head">
    <div class="wrap">
        <p class="kicker">Historique</p>
        <h1>{{ $page?->title ?? 'Notre histoire' }}</h1>
    </div>
</header>

@if ($page?->content)
<section class="wrap section">
    <details class="history" @if (! request()->hasAny(['annee', 'q', 'nom'])) open @endif>
        <summary>Des origines (1950) à aujourd'hui</summary>
        <div class="prose narrow">{!! $page->content !!}</div>
    </details>
</section>
@endif

<section class="wrap section" id="archives">
    <h2 class="section-title">Les concerts passés <small>({{ $total }} depuis 2012)</small></h2>

    <form method="get" action="{{ route('archive') }}#archives" class="filters">
        <label>
            <span>Rechercher un artiste, un compositeur…</span>
            <input type="search" name="q" value="{{ $q }}" placeholder="Schubert, Duo Sublima, Boverie…">
        </label>
        <label>
            <span>Organisé par</span>
            <select name="nom">
                <option value="">ACDM et FoliaMusica</option>
                @foreach (\App\Enums\Brand::cases() as $b)
                    <option value="{{ $b->value }}" @selected($brand === $b)>{{ $b->getLabel() }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn" type="submit">Rechercher</button>
    </form>

    @if ($q === '')
        <nav class="years" aria-label="Années">
            @foreach ($years as $y)
                <a href="{{ route('archive', array_filter(['annee' => $y, 'nom' => $brand?->value])) }}#archives" @class(['active' => $y === $year])>{{ $y }}</a>
            @endforeach
        </nav>
    @else
        <p class="results">{{ $events->count() }} résultat(s) pour « {{ $q }} ». <a href="{{ route('archive') }}#archives">Effacer</a></p>
    @endif

    @if ($events->isEmpty())
        <p class="empty">Aucun concert trouvé.</p>
    @else
        <div class="event-list archive-list">
            @foreach ($events as $event)
                @include('site.partials.event-card', ['event' => $event, 'compact' => true])
            @endforeach
        </div>
    @endif
</section>
@endsection
