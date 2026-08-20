{{--
  Template Name: Now
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container wide">
    <p class="eyebrow">{{ \App\field('now_kicker', __('Now', 'sage')) }}</p>
    <h1 class="display-title is-hero">{{ \App\field('now_h1', __('What I’m doing now.', 'sage')) }}</h1>
    <p class="lead">{{ \App\field('now_lede', __('A short list of where my time is going, updated August 2026.', 'sage')) }}</p>
  </header>
  <section class="pf-section">
    <div class="container measure">
      <ol class="now-list">
        @foreach (\App\field_lines('now_items', [
          __('Full-time Power Platform work.', 'sage'),
          __('Raising kids in Gettysburg. Nights and weekends are scarce, so I keep extra projects small.', 'sage'),
          __('This Sage 11 site is a notebook: writing, snippets, and example shops.', 'sage'),
          __('Sharing notes on this blog, DEV.to, Bluesky, and Reddit.', 'sage'),
          __('Helping with a few WordPress and Power Platform builds when I have room — including work for agencies who need a developer, not another marketer.', 'sage'),
        ]) as $item)
          <li>{{ $item }}</li>
        @endforeach
      </ol>
    </div>
  </section>
  @include('partials.cta-band', [
    'kicker' => __('Room in the calendar', 'sage'),
    'title' => \App\field('now_link', __('Say hello', 'sage')),
    'text' => __('If a note would help, send it. I usually reply in one or two business days.', 'sage'),
  ])
@endsection
