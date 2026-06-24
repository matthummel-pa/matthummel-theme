@extends('layouts.app')

@section('content')
  <section class="error-404 container">
    <p class="eyebrow">{{ __('Error 404', 'sage') }}</p>
    <h1 class="display-title is-hero">{{ __('Page not found', 'sage') }}</h1>
    <p class="lead">{{ __("That page doesn't exist or may have moved. Try a search, or head back home.", 'sage') }}</p>
    <div class="search-wrap">{!! get_search_form(false) !!}</div>
    <div class="btn-row">
      <a class="btn" href="{{ home_url('/') }}">{{ __('Back home', 'sage') }}</a>
    </div>
  </section>
@endsection
