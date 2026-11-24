@extends('layouts.site', [
    'title' => 'Ils ont joué chez nous : musiciens et ensembles invités',
    'description' => 'Les '.$people->count().' musiciens et '.$ensembles->count().' ensembles qui se sont produits aux Concerts du Dimanche Matin et avec FoliaMusica à Liège : solistes, chambristes, lauréats de concours, artistes des conservatoires royaux.',
])

@section('content')
<header class="page-head">
    <div class="wrap">
        <p class="kicker">Nos artistes</p>
        <h1>Ils ont joué chez nous</h1>
        <p class="lead">{{ $people->count() }} musiciens et {{ $ensembles->count() }} ensembles se sont produits à nos concerts depuis 2012 : solistes, chambristes, lauréats de concours, musiciens issus de nos conservatoires royaux et du monde entier.</p>
    </div>
</header>

<section class="wrap section">
    <p class="crosslink">Vous êtes musicien et souhaitez vous produire chez nous ? <a href="{{ route('page', 'musiciens') }}">Voici comment nous contacter</a>.</p>
</section>

@if ($ensembles->isNotEmpty())
<section class="wrap section">
    <h2 class="section-title">Ensembles et formations</h2>
    <ul class="artist-grid">
        @foreach ($ensembles as $e)
            <li>
                <a href="{{ route('archive', ['q' => $e['name']]) }}#archives">{{ $e['name'] }}</a>
                <small>{{ $e['count'] > 1 ? $e['count'].' concerts' : '1 concert' }} · {{ $e['last'] }}@if ($e['upcoming']) · <strong>à l'affiche</strong>@endif</small>
            </li>
        @endforeach
    </ul>
</section>
@endif

<section class="section section-alt">
    <div class="wrap">
        <h2 class="section-title">Musiciens</h2>
        @foreach ($people->groupBy(fn ($p) => mb_strtoupper(\Illuminate\Support\Str::ascii(mb_substr(\Illuminate\Support\Str::afterLast($p['name'], ' '), 0, 1)))) as $letter => $group)
            <h3 class="letter">{{ $letter }}</h3>
            <ul class="artist-grid">
                @foreach ($group as $p)
                    <li>
                        <a href="{{ route('archive', ['q' => $p['name']]) }}#archives">{{ $p['name'] }}</a>
                        <small>{{ $p['roles_label'] ?: 'musicien' }} · {{ $p['count'] > 1 ? $p['count'].' concerts' : $p['last'] }}@if ($p['upcoming']) · <strong>à l'affiche</strong>@endif</small>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>
</section>
@endsection
