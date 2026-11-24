@extends('layouts.site', ['title' => $page->title, 'description' => $page->meta_description])

@section('content')
<header class="page-head">
    <div class="wrap">
        <h1>{{ $page->title }}</h1>
    </div>
</header>
<section class="wrap section">
    <div class="prose narrow">{!! $page->content !!}</div>
</section>
@endsection
