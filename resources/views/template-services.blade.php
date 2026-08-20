{{--
  Template Name: Services
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container wide">
    <p class="eyebrow">{{ \App\field('svc_kicker', __('Services', 'sage')) }}</p>
    <h1 class="display-title is-hero">{{ \App\field('svc_h1', __('If you want a hand.', 'sage')) }}</h1>
    <p class="lead">{{ \App\field('svc_lede', __('Most of this site is free to read and copy. If you run a shop, a nonprofit, a small team, or an agency that needs a WordPress or Power Platform hand, you can write. I have a full-time Power Platform job, so I only take a few extra projects at a time.', 'sage')) }}</p>
  </header>

  @include('partials.audience', ['alt' => true])

  <section class="pf-section">
    <div class="container wide">
      <h2 class="display-title is-section">{{ \App\field('svc_ways_h2', __('Ways I can help', 'sage')) }}</h2>
      <ol class="svc-list svc-list--numbered">
        @foreach (\App\field_rows('svc_items', [
          ['title' => __('WordPress sites', 'sage'), 'text' => __('New sites, old sites that need care, and Sage 11 themes. Pages you can edit, explained in plain words.', 'sage')],
          ['title' => __('Power Platform', 'sage'), 'text' => __('Power Apps, Power Automate, and SharePoint. Everyday work that still lives in email or spreadsheets can often live in a small app instead.', 'sage')],
          ['title' => __('Small apps', 'sage'), 'text' => __('When WordPress is the wrong tool. React, APIs, and data — like Keepary on GitHub, which you can read.', 'sage')],
          ['title' => __('Cleanup', 'sage'), 'text' => __('Speed, accessibility, and search. A short list of fixes, explained in plain words.', 'sage')],
        ]) as $item)
          <li class="svc-item">
            <h3>{{ $item['title'] }}</h3>
            <p>{{ $item['text'] }}</p>
          </li>
        @endforeach
      </ol>
    </div>
  </section>

  <section class="pf-section pf-section--alt">
    <div class="container wide">
      <h2 class="display-title is-section">{{ \App\field('svc_process_h2', __('How a project usually goes', 'sage')) }}</h2>
      <ol class="process-list">
        @foreach (\App\field_rows('svc_process', [
          ['title' => __('Write', 'sage'), 'text' => __('Tell me who it is for and what is broken or missing. A paragraph is enough.', 'sage')],
          ['title' => __('Scope', 'sage'), 'text' => __('I send a short list of work, a timeline, and what I will not do (ads, social, ongoing retainers).', 'sage')],
          ['title' => __('Ship', 'sage'), 'text' => __('You get pages you can edit, notes in plain words, and the repo if the work is public.', 'sage')],
        ]) as $step)
          <li>
            <h3>{{ $step['title'] }}</h3>
            <p>{{ $step['text'] }}</p>
          </li>
        @endforeach
      </ol>
    </div>
  </section>

  <section class="pf-section">
    <div class="container measure faq-wrap">
      <h2 class="display-title is-section">{{ \App\field('svc_faq_h2', __('Quick answers', 'sage')) }}</h2>
      <div class="faq-list">
        @foreach (\App\field_rows('svc_faq', [
          ['title' => __('Do you take agency overflow?', 'sage'), 'text' => __('Yes, when the work is a real WordPress or Power Platform build. You keep the client relationship. I stay the developer.', 'sage')],
          ['title' => __('Can I copy the code for free?', 'sage'), 'text' => __('Yes. Public repos and snippets are there to borrow. A note if you ship something with them is kind, not required.', 'sage')],
          ['title' => __('Do you run ads or social?', 'sage'), 'text' => __('No. Local Gettysburg marketing lives at Ridges & Valleys. This site is for building and sharing.', 'sage')],
        ]) as $faq)
          <details>
            <summary>{{ $faq['title'] }}</summary>
            <p>{{ $faq['text'] }}</p>
          </details>
        @endforeach
      </div>
      <p>{!! \App\field_html('svc_fair', __('I don’t run ads or social accounts for clients. Local Gettysburg marketing lives at <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a>. Here, sharing comes first. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.', 'sage')) !!}</p>
    </div>
  </section>

  @include('partials.cta-band', [
    'kicker' => \App\field('svc_fair_h2', __('A fair picture', 'sage')),
    'title' => __('Send a short note', 'sage'),
    'text' => __('Tell me if you are a developer, a shop, or an agency. That helps me reply in the right shape.', 'sage'),
  ])
@endsection
