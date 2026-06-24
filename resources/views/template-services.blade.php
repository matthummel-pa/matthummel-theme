{{--
  Template Name: Services
--}}

@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  <div class="container">
    @if (get_the_content())
      <div class="entry-content post-prose">@php(the_content())</div>
    @endif

    <div class="card-grid services-grid">
      <div class="service-card">
        <h3>{{ __('Web Development', 'matthummel') }}</h3>
        <p>{{ __('Fast, accessible sites and web apps — modern front-end, performance, and SEO.', 'matthummel') }}</p>
      </div>
      <div class="service-card">
        <h3>{{ __('WordPress Development', 'matthummel') }}</h3>
        <p>{{ __('Custom themes, Gutenberg blocks, and code-first Sage builds without page-builder bloat.', 'matthummel') }}</p>
      </div>
      <div class="service-card">
        <h3>{{ __('Power Platform', 'matthummel') }}</h3>
        <p>{{ __('Power Apps, Power Automate, and Dataverse solutions across Microsoft 365.', 'matthummel') }}</p>
      </div>
    </div>
  </div>

  @include('partials.cta')
@endsection
