{{--
  Template Name: Services
--}}
@extends('layouts.app')

@section('content')
  @component('partials.page-hero')
    <p class="eyebrow">{{ \App\field('svc_kicker', __('Services', 'sage')) }}</p>
    <h1 class="display-title is-hero">{{ \App\field('svc_h1', __('If you want a hand.', 'sage')) }}</h1>
    <p class="lead">{{ \App\field('svc_lede', __('Most of this site is free to read and copy. If you need a WordPress site, a plugin, or another web app in Gettysburg, you can write. I take a few extra projects; Power Platform is not my main focus.', 'sage')) }}</p>
  @endcomponent

  @include('partials.audience', ['alt' => true])

  <section class="pf-section">
    <div class="container wide">
      <h2 class="display-title is-section">{{ \App\field('svc_ways_h2', __('Ways I can help', 'sage')) }}</h2>
      <ol class="svc-list svc-list--numbered">
        @foreach (\App\field_rows('svc_items', [
          ['title' => __('WordPress sites', 'sage'), 'text' => __('New sites, old sites that need care, and themes you can edit. Plain words. Pages that work.', 'sage')],
          ['title' => __('WordPress plugins', 'sage'), 'text' => __('Small plugins that do one job well. You can read the code. You can change it.', 'sage')],
          ['title' => __('Other web apps', 'sage'), 'text' => __('When WordPress is the wrong tool. React, APIs, and data — like Keepary on GitHub.', 'sage')],
          ['title' => __('Power Platform', 'sage'), 'text' => __('Some Power Apps and Power Automate work, when a spreadsheet should be a small app instead. This is not my main focus.', 'sage')],
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
          ['title' => __('Do you take agency overflow?', 'sage'), 'text' => __('Yes, when the work is a real WordPress site, plugin, or other web app. You keep the relationship. I stay the developer.', 'sage')],
          ['title' => __('Can I copy the code for free?', 'sage'), 'text' => __('Yes. Public repos and snippets are there to borrow. A note if you ship something with them is kind, not required.', 'sage')],
          ['title' => __('Do you run ads or social?', 'sage'), 'text' => __('No. Local Gettysburg marketing lives at Ridges & Valleys. This site is for building and sharing.', 'sage')],
        ]) as $faq)
          <details>
            <summary>{{ $faq['title'] }}</summary>
            <p>{{ $faq['text'] }}</p>
          </details>
        @endforeach
      </div>
      <p>{!! \App\field_html('svc_fair', __('I don’t run ads or social accounts for shops. Local Gettysburg marketing lives at <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a>. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.', 'sage')) !!}</p>
    </div>
  </section>

  @include('partials.cta-band', [
    'kicker' => \App\field('svc_fair_h2', __('A fair picture', 'sage')),
    'title' => __('Send a short note', 'sage'),
    'text' => __('Tell me if you are a developer, a shop, or an agency. That helps me reply in the right shape.', 'sage'),
  ])
@endsection
