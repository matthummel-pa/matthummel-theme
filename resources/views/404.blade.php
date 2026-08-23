@extends('layouts.app')

@section('content')
  @component('partials.page-hero', ['tag' => 'section', 'extra' => 'error-404'])
    <p class="eyebrow">{{ __('404', 'sage') }}</p>
    <h1 class="display-title is-hero">{{ __('That page is not here.', 'sage') }}</h1>
    <p class="lead">{{ __('It may have moved. Search, go home, or pick a door.', 'sage') }}</p>
    <div class="search-wrap">{!! get_search_form(false) !!}</div>
    <ul class="elsewhere error-links">
      <li><a href="{{ home_url('/') }}">{{ __('Home', 'sage') }}</a></li>
      <li><a href="{{ home_url('/projects/') }}">{{ __('Example sites', 'sage') }}</a></li>
      <li><a href="{{ home_url('/code/') }}">{{ __('Code', 'sage') }}</a></li>
      <li><a href="{{ get_permalink(get_option('page_for_posts')) ?: home_url('/blog/') }}">{{ __('Journal', 'sage') }}</a></li>
      <li><a href="{{ home_url('/contact/') }}">{{ __('Say hello', 'sage') }}</a></li>
    </ul>
  @endcomponent
@endsection
