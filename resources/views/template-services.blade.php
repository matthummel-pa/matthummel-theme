{{--
  Template Name: Services
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container">
    <h1 class="display-title is-hero">{{ \App\field('svc_h1', __('If you want a hand.', 'sage')) }}</h1>
    <p class="lead">{{ \App\field('svc_lede', __('Most of this site is free to read and copy. If you run a shop, a nonprofit, or a small team and a WordPress site or an internal app would help, you can write. I have a full-time Power Platform job, so I only take a few extra projects at a time.', 'sage')) }}</p>
  </header>

  <section class="pf-section">
    <div class="container">
      <div class="svc-list">
        @foreach (\App\field_rows('svc_items', [
          ['title' => __('WordPress sites', 'sage'), 'text' => __('New sites, old sites that need care, and Sage 11 themes. Pages you can edit, explained in plain words.', 'sage')],
          ['title' => __('Power Platform', 'sage'), 'text' => __('Power Apps, Power Automate, and SharePoint. Everyday work that still lives in email or spreadsheets can often live in a small app instead.', 'sage')],
          ['title' => __('Small apps', 'sage'), 'text' => __('When WordPress is the wrong tool. React, APIs, and data — like Keepary on GitHub, which you can read.', 'sage')],
          ['title' => __('Cleanup', 'sage'), 'text' => __('Speed, accessibility, and search. A short list of fixes, explained in plain words.', 'sage')],
        ]) as $item)
          <article class="svc-item">
            <h2>{{ $item['title'] }}</h2>
            <p>{{ $item['text'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="pf-section">
    <div class="container measure">
      <h2 class="display-title is-section">{{ \App\field('svc_fair_h2', __('A fair picture', 'sage')) }}</h2>
      <p>{!! \App\field_html('svc_fair', __('I don’t run ads or social accounts for clients. Local Gettysburg marketing lives at <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a>. Here, sharing comes first. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.', 'sage')) !!}</p>
    </div>
  </section>
@endsection
