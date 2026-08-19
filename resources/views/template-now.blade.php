{{--
  Template Name: Now
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container">
    <h1 class="display-title is-hero">{{ \App\field('now_h1', __('What I’m doing now.', 'sage')) }}</h1>
    <p class="lead">{{ \App\field('now_lede', __('A short list of where my time is going, updated August 2026.', 'sage')) }}</p>
  </header>
  <section class="pf-section">
    <div class="container measure">
      <ul>
        @foreach (\App\field_lines('now_items', [
          __('Full-time Power Platform work.', 'sage'),
          __('Raising kids in Gettysburg. Nights and weekends are scarce, so I keep extra projects small.', 'sage'),
          __('This Sage 11 site is a notebook: writing, snippets, and example shops.', 'sage'),
          __('Sharing notes on this blog, DEV.to, Bluesky, and Reddit.', 'sage'),
          __('Helping with a few WordPress and Power Platform builds when I have room.', 'sage'),
        ]) as $item)
          <li>{{ $item }}</li>
        @endforeach
      </ul>
      <p><a href="{{ home_url('/contact/') }}">{{ \App\field('now_link', __('Say hello', 'sage')) }}</a></p>
    </div>
  </section>
@endsection
