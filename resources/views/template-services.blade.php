{{--
  Template Name: Services
--}}
@extends('layouts.app')

@php
  $services = \App\mh_about_page_services();
  $faqs     = \App\mh_services_faq();
@endphp

@section('content')

@php
  $faqSchema = array_map(
    fn ($f) => ['@type' => 'Question', 'name' => $f['title'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_strip_all_tags($f['text'])]],
    $faqs
  );
  $faqJsonLd = $faqs !== []
    ? json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqSchema], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)
    : '';
@endphp
@if ($faqJsonLd !== '')
<script type="application/ld+json">{!! $faqJsonLd !!}</script>
@endif

@component('partials.page-hero', ['split' => true, 'asideLabel' => __('How I work', 'sage')])
  <p class="eyebrow">{{ \App\field('svc_kicker', __('How I work', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('svc_h1', __('Services', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('svc_lede', __('WordPress themes, plugins, and web apps for shops and agencies. Hire me for a production build, overflow, or a full-time role.', 'sage')) }}
  </p>
  <div class="page-header-split__actions">
    <a class="btn" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!} {{ __('Say hello', 'sage') }}
    </a>
    <a class="h-text-arrow" href="{{ esc_url(\App\mh_work_listing_url()) }}">
      {{ __('Browse projects', 'sage') }} <span aria-hidden="true">→</span>
    </a>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/services',
      'icon' => 'briefcase',
      'title' => __('What I take on', 'sage'),
      'meta' => __('Shops · agencies · roles', 'sage'),
      'link' => [
        'label' => __('Hire page', 'sage'),
        'href' => home_url('/hire/'),
      ],
    ])
  @endslot
@endcomponent

@include('partials.page-nav', [
  'pills' => [
    ['build', __('What I build', 'sage')],
    ['process', __('Process', 'sage')],
    ['faq', __('FAQ', 'sage')],
  ],
])

<section class="pf-section" id="build" aria-labelledby="svc-build-heading">
  <div class="container wide">
    <p class="eyebrow">{{ __('Work', 'sage') }}</p>
    <h2 id="svc-build-heading" class="display-title is-section">
      {{ \App\field('svc_addons_h2', __('What I build.', 'sage')) }}
    </h2>
    <p class="sec-intro" style="max-width:52ch">
      {{ \App\field('svc_addons_intro', \App\mh_adjacent_range_copy()) }}
    </p>
    <div class="about-services" style="margin-top:2rem">
      @foreach ($services as $i => $svc)
        <article class="about-svc-card">
          <span class="about-svc-card__n" aria-hidden="true">{{ sprintf('%02d', $i + 1) }}</span>
          <div class="about-svc-card__icon">{!! \App\mh_svg_icon($svc['icon'], 22) !!}</div>
          <h3 class="about-svc-card__title">{{ $svc['title'] }}</h3>
          <p class="about-svc-card__body">{{ $svc['body'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="pf-section pf-section--alt" id="process" aria-labelledby="svc-process-heading">
  <div class="container wide">
    <p class="eyebrow">{{ __('Process', 'sage') }}</p>
    <h2 id="svc-process-heading" class="display-title is-section">
      {{ \App\field('svc_price_h2', __('From hello to handoff.', 'sage')) }}
    </h2>
    <p class="sec-intro" style="max-width:52ch">
      {{ \App\field('svc_price_intro', __('A short note is enough to start. I send a written scope before anything begins.', 'sage')) }}
    </p>
    <div class="hire-steps" style="margin-top:2rem">
      @foreach ([
        ['Write', 'A few sentences. No spec, no pitch deck.', '1–2 days'],
        ['Scope', 'I send a written list of work, timeline, and what is out of scope.', '2–4 days'],
        ['Build', 'Staged previews on real pages. Changes before launch.', '1–4 weeks'],
        ['Yours', 'Domain, hosting, code, and a plain-language admin guide — all transferred.', 'Handoff day'],
      ] as $i => [$title, $body, $time])
        <div class="hire-step">
          <div class="hire-step__head">
            <span class="hire-step__num">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
            <span class="hire-step__time">{!! \App\mh_svg_icon('calendar', 13) !!} {{ $time }}</span>
          </div>
          <h3 class="hire-step__title">{{ $title }}</h3>
          <p class="hire-step__body">{{ $body }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

@if ($faqs !== [])
<section class="pf-section" aria-labelledby="svc-faq-heading" id="faq">
  <div class="container wide svc-faq-layout">
    <div class="svc-faq-aside">
      <p class="eyebrow">{{ __('Questions', 'sage') }}</p>
      <h2 id="svc-faq-heading" class="display-title is-section">
        {{ \App\field('svc_faq_h2', __('Quick answers', 'sage')) }}
      </h2>
      <p class="svc-faq-aside__intro">{{ __('How I work, and what you own after handoff.', 'sage') }}</p>
      <div class="svc-faq-aside__cta">
        <p>{{ __('Question not here?', 'sage') }}</p>
        <a class="btn btn--sm" href="{{ home_url('/contact/') }}">
          {!! \App\mh_svg_icon('mail', 14) !!} {{ __('Ask me directly', 'sage') }}
        </a>
      </div>
    </div>
    <div>
      <div class="faq-list">
        @foreach ($faqs as $i => $faq)
          <details {{ $i === 0 ? 'open' : '' }}>
            <summary>{{ $faq['title'] }}</summary>
            <p>{!! wp_kses_post($faq['text']) !!}</p>
          </details>
        @endforeach
      </div>
    </div>
  </div>
</section>
@endif

@include('partials.cta-band', [
  'kicker' => __('Let’s work together', 'sage'),
  'title' => \App\field('svc_fair_h2', __('Ready to talk?', 'sage')),
  'text' => wp_strip_all_tags(\App\field_html('svc_fair', __('Write about a build, overflow, or a role. I reply within one business day (ET).', 'sage'))),
  'label' => __('Say hello', 'sage'),
  'href' => home_url('/contact/'),
  'secondary' => '',
])

@endsection
