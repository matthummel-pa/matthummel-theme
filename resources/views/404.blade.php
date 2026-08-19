@extends('layouts.app')

@section('content')
  <section class="error-404 container" style="padding:3rem 0">
    <h1 class="display-title is-hero">{{ __('That page is not here.', 'sage') }}</h1>
    <p class="lead">{{ __('It may have moved. Search, or go home.', 'sage') }}</p>
    <div class="search-wrap">{!! get_search_form(false) !!}</div>
    <p><a class="btn" href="{{ home_url('/') }}">{{ __('Home', 'sage') }}</a></p>
  </section>
@endsection
