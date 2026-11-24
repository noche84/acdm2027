@extends('layouts.site', ['title' => $title])

@section('content')
<section class="wrap section">
    <div class="narrow notice-page">
        <h1>{{ $title }}</h1>
        <p class="lead">{{ $message }}</p>
        <p><a class="btn" href="{{ route('agenda') }}">Voir l'agenda</a></p>
    </div>
</section>
@endsection
