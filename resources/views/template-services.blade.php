{{--
  Template Name: Services
--}}
@extends('layouts.app')

@php
  $addons   = \App\mh_acreline_addon_products();
  $theme    = \App\mh_services_acreline_theme();
  $packages = \App\mh_services_pricing();
  $faqs     = \App\mh_services_faq();
@endphp

@section('content')

@php
  $faqSchema = array_map(
    fn ($f) => ['@type' => 'Question', 'name' => $f['title'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_strip_all_tags($f['text'])]],
    $faqs
  );
  $faqJsonLd = json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqSchema], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
@endphp
<script type="application/ld+json">{!! $faqJsonLd !!}</script>

@component('partials.page-hero', ['split' => true, 'asideLabel' => __('Acreline', 'sage')])
  <p class="eyebrow">{{ \App\field('svc_kicker', __('Acreline add-ons · custom work', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('svc_h1', __('Services', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('svc_lede', __('Install, setup, listings, and care for Acreline — plus custom WordPress when a shop or agency needs a build.', 'sage')) }}
  </p>
  <div class="page-header-split__actions">
    <a class="btn" href="#addons">
      {!! \App\mh_svg_icon('briefcase', 16) !!} {{ __('See add-ons', 'sage') }}
    </a>
    <a class="h-text-arrow" href="{{ esc_url($theme['demo']) }}" target="_blank" rel="noopener">
      {{ __('Live demo', 'sage') }} <span aria-hidden="true">→</span>
    </a>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/services',
      'icon' => 'wordpress',
      'title' => $theme['title'],
      'meta' => $theme['price'] !== '' ? $theme['price'] : __('Listing theme', 'sage'),
      'stats' => [
        ['value' => (string) count($addons), 'label' => __('Add-ons', 'sage')],
        ['value' => __('From $99', 'sage'), 'label' => __('Shop services', 'sage')],
        ['value' => __('Checkout', 'sage'), 'label' => __('Live products', 'sage')],
        ['value' => __('Custom', 'sage'), 'label' => __('Floors below', 'sage')],
      ],
      'link' => [
        'label' => __('View Acreline', 'sage'),
        'href' => $theme['permalink'],
      ],
    ])
  @endslot
@endcomponent

<section class="svc-theme-band" aria-label="{{ __('Acreline theme', 'sage') }}">
  <div class="container wide svc-theme-band__inner">
    <p class="svc-theme-band__copy">
      {!! \App\field_html('svc_theme_note', __('Need the theme first? <a href="/product/acreline/">Acreline</a> is the listing theme. <a href="https://acreline.matthummel.com/">See the demo</a>.', 'sage')) !!}
    </p>
    <div class="svc-theme-band__actions">
      <a class="btn btn--sm" href="{{ esc_url($theme['permalink']) }}">{{ __('View Acreline', 'sage') }}</a>
      <a class="h-text-arrow" href="{{ esc_url($theme['demo']) }}" target="_blank" rel="noopener">
        {{ __('Live demo', 'sage') }} <span aria-hidden="true">→</span>
      </a>
    </div>
  </div>
</section>

<section class="pf-section" aria-labelledby="svc-addons-heading" id="addons">
  <div class="container wide">
    <p class="eyebrow">{{ __('Shop', 'sage') }}</p>
    <h2 id="svc-addons-heading" class="display-title is-section">
      {{ \App\field('svc_addons_h2', __('Acreline add-ons.', 'sage')) }}
    </h2>
    <p class="sec-intro" style="max-width:52ch">
      {{ \App\field('svc_addons_intro', __('Live shop products with checkout. Prices below come from WooCommerce when the product exists.', 'sage')) }}
    </p>

    <div class="svc-addon-grid">
      @foreach ($addons as $addon)
        <article class="svc-addon-card">
          <div class="svc-addon-card__head">
            <div class="svc-addon-card__icon">{!! \App\mh_svg_icon($addon['icon'], 22) !!}</div>
            <p class="svc-addon-card__price">{{ $addon['price'] }}</p>
          </div>
          <h3 class="svc-addon-card__title">
            <a href="{{ esc_url($addon['permalink']) }}">{{ $addon['title'] }}</a>
          </h3>
          <p class="svc-addon-card__body">{{ $addon['blurb'] }}</p>
          <div class="svc-addon-card__actions">
            <a class="btn btn--sm" href="{{ esc_url($addon['permalink']) }}">
              {{ __('View details', 'sage') }}
            </a>
            @if ($addon['add_to_cart_url'] !== '')
              <a class="h-text-arrow" href="{{ esc_url($addon['add_to_cart_url']) }}">
                {{ __('Add to cart', 'sage') }} <span aria-hidden="true">→</span>
              </a>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

@if ($packages !== [])
<section class="pf-section pf-section--alt" aria-labelledby="svc-price-heading" id="custom">
  <div class="container wide">
    <p class="eyebrow">{{ __('Hire', 'sage') }}</p>
    <h2 id="svc-price-heading" class="display-title is-section">
      {{ \App\field('svc_price_h2', __('Custom WordPress.', 'sage')) }}
    </h2>
    <p class="sec-intro" style="max-width:52ch">
      {{ \App\field('svc_price_intro', __('Floors for a custom build or agency overflow — not a menu. Write and I will quote the actual scope.', 'sage')) }}
    </p>

    <div class="svc-custom-grid">
      @foreach ($packages as $pkg)
        <article class="svc-audience-card svc-price-card">
          @if ($pkg['price'] !== '')
            <p class="svc-price-card__amount">{{ $pkg['price'] }}</p>
          @endif
          <h3 class="svc-audience-card__title">{{ $pkg['title'] }}</h3>
          <p class="svc-audience-card__body">{{ $pkg['text'] }}</p>
          @php
            $pkgHref = (string) ($pkg['href'] ?? '/contact/');
            $pkgHref = preg_match('#^(https?:)?//#i', $pkgHref) === 1 ? $pkgHref : home_url($pkgHref);
          @endphp
          <a class="svc-audience-card__link" href="{{ esc_url($pkgHref) }}">
            {{ $pkg['cta'] }} →
          </a>
        </article>
      @endforeach
    </div>
    <p class="svc-price-note">{!! \App\field_html('svc_price_note', __('Need something else? <a href="/contact/">Ask for a custom quote</a>. I reply within one business day (ET).', 'sage')) !!}</p>
  </div>
</section>
@endif

<section class="pf-section" aria-labelledby="svc-faq-heading" id="faq">
  <div class="container wide svc-faq-layout">
    <div class="svc-faq-aside">
      <p class="eyebrow">{{ __('Questions', 'sage') }}</p>
      <h2 id="svc-faq-heading" class="display-title is-section">
        {{ \App\field('svc_faq_h2', __('Quick answers', 'sage')) }}
      </h2>
      <p class="svc-faq-aside__intro">{{ __('Add-ons, custom builds, and what you own after handoff.', 'sage') }}</p>
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

<section class="cta-band" aria-labelledby="svc-cta-heading" data-reveal>
  <div class="container wide cta-band-inner">
    <div class="cta-band__copy">
      <p class="eyebrow eyebrow--on-dark">{{ __('Let’s work together', 'sage') }}</p>
      <h2 id="svc-cta-heading" class="display-title is-section">
        {{ \App\field('svc_fair_h2', __('Ready to talk?', 'sage')) }}
      </h2>
      <p>{!! \App\field_html('svc_fair', __('Pick an Acreline add-on in the shop, or write about a custom build. I reply within one business day (ET).', 'sage')) !!}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!} {{ __('Say hello', 'sage') }}
      </a>
      <a class="btn btn-ghost" href="#addons">
        {{ __('See add-ons', 'sage') }}
      </a>
      <p class="cta-band__note">{{ \App\mh_reply_sla('note') }}</p>
    </div>
  </div>
</section>

@endsection
