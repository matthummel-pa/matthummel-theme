{{--
  Single product — marketplace-quality landing page.

  Layout follows professional WordPress theme/plugin seller conventions:
  hero with buy card → screenshots → features → story → included → purchase
  → technical → docs → FAQ → CTA.

  Data source: mh_product_entry() merges product-catalog.json with _mh_project_*
  meta saved directly on the WooCommerce product, so the admin can override any
  field without touching the JSON file.

  @see https://woocommerce.com/document/template-structure/
  @version 3.4.0
--}}
@extends('layouts.app')

@section('content')
@php
  do_action('get_header', 'shop');

  $productId    = (int) get_the_ID();
  $productTitle = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $shopUrl      = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
  $projectsUrl  = \App\mh_theme_catalog_url();

  $wcProduct    = null;
  $priceHtml    = '';
  $regularPrice = '';
  $isFree       = false;
  $isOnSale     = false;
  $projectId    = 0;

  if (function_exists('wc_get_product')) {
    $wcProduct = wc_get_product($productId);
    if ($wcProduct) {
      $projectId    = (int) $wcProduct->get_meta('_mh_product_project_id');
      $priceHtml    = $wcProduct->get_price_html();
      $regularPrice = (string) $wcProduct->get_regular_price();
      $isFree       = (float) $wcProduct->get_price() <= 0.0;
      $isOnSale     = ! $isFree && $wcProduct->is_on_sale();
    }
  }

  // Load product entry: WC meta overrides catalog JSON.
  $entry        = \App\mh_product_entry($productId);
  $eyebrow      = trim((string) ($entry['eyebrow'] ?? ''));
  $summary      = trim((string) ($entry['summary'] ?? ''));
  $blurb        = trim((string) ($entry['blurb'] ?? ''));
  $challenge    = trim((string) ($entry['challenge'] ?? ''));
  $approach     = trim((string) ($entry['approach'] ?? ''));
  $result       = trim((string) ($entry['result'] ?? ''));
  $audience     = trim((string) ($entry['audience'] ?? ''));
  $architecture = trim((string) ($entry['architecture'] ?? ''));
  $handoff      = trim((string) ($entry['handoff'] ?? ''));
  $benefits     = is_array($entry['benefits'] ?? null) ? $entry['benefits'] : [];
  $deliverables = is_array($entry['deliverables'] ?? null) ? $entry['deliverables'] : [];
  $metrics      = is_array($entry['metrics'] ?? null) ? $entry['metrics'] : [];
  $faq          = is_array($entry['faq'] ?? null) ? $entry['faq'] : [];
  $screenshots  = is_array($entry['screenshots'] ?? null) ? $entry['screenshots'] : [];
  $productType  = (string) ($entry['product_type'] ?? 'theme');
  $demoUrl      = trim((string) ($entry['demo'] ?? ''));
  $githubUrl    = trim((string) ($entry['github'] ?? ''));
  $docs         = is_array($entry['docs'] ?? null) ? $entry['docs'] : [];
  $filesIncl    = is_array($entry['files_included'] ?? null) ? $entry['files_included'] : [];
  $tagline      = trim((string) ($entry['brand_tagline'] ?? ''));
  $version      = trim((string) ($entry['version'] ?? ''));
  $compatible   = trim((string) ($entry['compatible'] ?? ''));
  $license      = trim((string) ($entry['license'] ?? ''));
  $support      = trim((string) ($entry['support'] ?? ''));
  $tech         = is_array($entry['tech'] ?? null) ? $entry['tech'] : [];
  $blocks       = is_array($entry['blocks'] ?? null) ? $entry['blocks'] : [];

  $isTheme   = $productType === 'theme';
  $isPlugin  = $productType === 'plugin';
  $isService = $productType === 'service';
  $typeChrome = \App\mh_product_type_chrome($productType);

  if ($eyebrow === '') {
    $eyebrow = $typeChrome['eyebrow'];
  }

  $productPayload = \App\mh_shop_product_payload($productId);
  $buyUrl = '';
  if (is_array($productPayload)) {
    $buyUrl   = (string) ($productPayload['add_to_cart_url'] ?? '');
    $isFree   = (bool) ($productPayload['is_free'] ?? $isFree);
    if ($priceHtml === '') {
      $priceHtml = (string) ($productPayload['price_html'] ?? '');
    }
    if ($regularPrice === '') {
      $regularPrice = (string) ($productPayload['regular_price'] ?? '');
    }
  }

  $helpUrl = add_query_arg([
    'project' => sanitize_title($productTitle),
    'intent'  => 'help',
    'who'     => 'business',
  ], home_url('/contact/'));

  $primaryLabel = \App\mh_product_buy_copy($productId, $isFree);

  $priceDisplay = $isFree ? __('Free', 'sage') : ('$'.ltrim($regularPrice, '$'));

  $gallerySlides = \App\mh_product_gallery_slides($productId, $entry, $wcProduct);
  $heroImage = (string) ($gallerySlides[0]['src'] ?? '');
  $heroImageAlt = (string) ($gallerySlides[0]['alt'] ?? sprintf(__('%s — featured screenshot', 'sage'), $productTitle));

  $crumbItems = [
    ['label' => __('Home', 'sage'),     'url' => home_url('/')],
    ['label' => __('Shop', 'sage'),     'url' => $projectsUrl],
    ['label' => $productTitle, 'current' => true],
  ];

  // JSON-LD: FAQ rich result.
  $faqSchema = array_map(
    fn ($f) => [
      '@type'          => 'Question',
      'name'           => (string) ($f[0] ?? ''),
      'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string) ($f[1] ?? '')],
    ],
    $faq
  );
  $faqJsonLd = $faqSchema !== []
    ? json_encode(
        ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqSchema],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
      )
    : '';

  // JSON-LD: SoftwareApplication for digital products; Service for shop services.
  $productSchema = [
    '@context'    => 'https://schema.org',
    '@type'       => $isService ? 'Service' : 'SoftwareApplication',
    'name'        => $productTitle,
    'description' => $blurb ?: $summary,
  ];
  if (! $isService) {
    $productSchema['applicationCategory'] = $productType === 'plugin' ? 'BusinessApplication' : 'WebApplication';
    $productSchema['operatingSystem'] = $productType === 'app' ? 'Browser' : 'WordPress';
  }
  if ($heroImage !== '') { $productSchema['image'] = $heroImage; }
  if ($version !== '') { $productSchema['softwareVersion'] = $version; }
  if ($demoUrl !== '')  { $productSchema['url'] = $demoUrl; }
  if ($license !== '')  { $productSchema['license'] = $license; }
  if ($priceHtml !== '') {
    $plainPrice = html_entity_decode(wp_strip_all_tags($priceHtml), ENT_QUOTES, 'UTF-8');
    $productSchema['offers'] = [
      '@type'         => 'Offer',
      'priceCurrency' => 'USD',
      'price'         => $isFree ? '0' : preg_replace('/[^0-9.]/', '', $plainPrice),
      'availability'  => 'https://schema.org/InStock',
    ];
  }
  $productJsonLd = json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
@endphp

@if ($faqJsonLd !== '')
  <script type="application/ld+json">{!! $faqJsonLd !!}</script>
@endif
@if ($productJsonLd !== '')
  <script type="application/ld+json">{!! $productJsonLd !!}</script>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     HERO — gallery (left) + buy box (right)
════════════════════════════════════════════════════════════════════════════ --}}
<header class="pf-product-hero mh-shop page-header--product" aria-label="{{ __('Product', 'sage') }}">
  <div class="container wide">
    @include('partials.woocommerce-crumb', ['items' => $crumbItems])
  </div>
  <div class="container wide pf-product-hero__inner">

    {{-- Left: product gallery --}}
    <div class="pf-product-gallery" data-product-gallery>
      @if ($gallerySlides !== [])
        <figure class="pf-product-gallery__stage">
          <button
            type="button"
            class="pf-product-gallery__main"
            id="pf-gallery-main"
            data-gallery-open
            data-gallery-index="0"
            aria-label="{{ __('Open screenshot', 'sage') }}"
          >
            <img
              src="{{ esc_url($heroImage) }}"
              alt="{{ esc_attr($heroImageAlt) }}"
              width="1200"
              height="750"
              loading="eager"
              decoding="async"
              data-gallery-main
            >
          </button>
        </figure>
        @if (count($gallerySlides) > 1)
          <div class="pf-product-gallery__thumbs" role="tablist" aria-label="{{ __('Product screenshots', 'sage') }}">
            @foreach ($gallerySlides as $i => $slide)
              <button
                type="button"
                class="pf-product-gallery__thumb{{ $i === 0 ? ' is-active' : '' }}"
                role="tab"
                id="pf-gallery-tab-{{ $i }}"
                aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                aria-controls="pf-gallery-main"
                data-gallery-index="{{ $i }}"
                data-gallery-src="{{ esc_url($slide['src']) }}"
                data-gallery-alt="{{ esc_attr($slide['alt']) }}"
              >
                <img
                  src="{{ esc_url($slide['src']) }}"
                  alt=""
                  width="160"
                  height="100"
                  loading="{{ $i < 4 ? 'eager' : 'lazy' }}"
                  decoding="async"
                >
                <span class="visually-hidden">{{ sprintf(__('Screenshot %d', 'sage'), $i + 1) }}</span>
              </button>
            @endforeach
          </div>
        @endif
      @else
        <div class="pf-product-gallery__stage pf-product-gallery__stage--empty">
          {!! \App\mh_product_fallback_markup($productId, $productTitle, $productType) !!}
        </div>
      @endif
    </div>

    {{-- Right: scannable buy box --}}
    <div class="pf-product-buybox pf-product-hero__card" aria-label="{{ __('Purchase', 'sage') }}">
      <p class="eyebrow pf-product-hero__eyebrow">{{ $eyebrow }}</p>
      <h1 class="display-title is-hero pf-product-hero__title">{{ $productTitle }}</h1>

      @if ($tagline !== '')
        <p class="pf-product-hero__tagline">{{ $tagline }}</p>
      @endif

      <p class="lead pf-product-hero__lead">{{ $blurb ?: $summary }}</p>

      <div class="pf-product-buybox__price">
        @if ($isOnSale)
          <span class="pf-sale-badge">{{ __('Sale', 'sage') }}</span>
        @endif
        @if ($isFree)
          <span class="pf-product-card__price-free">{{ __('Free', 'sage') }}</span>
        @elseif ($priceHtml !== '')
          <span class="pf-product-card__price-amount">{!! wp_kses_post($priceHtml) !!}</span>
        @endif
        @if (! $isFree && $priceHtml !== '')
          <span class="pf-product-card__price-note">
            {{ $isService ? __('one-time · scoped deliverable', 'sage') : __('one-time · instant download', 'sage') }}
          </span>
        @endif
      </div>

      <div class="pf-product-hero__actions">
        @if ($buyUrl !== '')
          <a class="btn pf-product-card__cta" href="{{ esc_url($buyUrl) }}">
            {!! \App\mh_svg_icon($isPlugin ? 'download' : ($isService ? 'briefcase' : 'cart'), 16) !!}
            {{ $primaryLabel }}
          </a>
        @endif
        @if ($demoUrl !== '')
          <a class="btn btn-outline pf-product-card__demo" href="{{ esc_url($demoUrl) }}" target="_blank" rel="noopener">
            {!! \App\mh_svg_icon('arrow-up-right', 16) !!} {{ __('Live demo', 'sage') }}
          </a>
        @endif
      </div>

      <ul class="pf-product-card__checklist">
        @if ($isService)
          <li>{!! \App\mh_svg_icon('check', 13) !!} {{ __('Clear scope and handoff', 'sage') }}</li>
        @elseif ($isFree)
          <li>{!! \App\mh_svg_icon('check', 13) !!} {{ __('Free download', 'sage') }}</li>
        @else
          <li>{!! \App\mh_svg_icon('check', 13) !!} {{ __('Instant digital download', 'sage') }}</li>
        @endif
        @if ($license !== '')
          <li>{!! \App\mh_svg_icon('check', 13) !!} {{ $license }}</li>
        @elseif (! $isService)
          <li>{!! \App\mh_svg_icon('check', 13) !!} {{ __('GPL licensed', 'sage') }}</li>
        @endif
        @foreach (array_slice($deliverables, 0, 3) as $d)
          <li>{!! \App\mh_svg_icon('check', 13) !!} {{ $d }}</li>
        @endforeach
      </ul>

      @if ($version !== '' || $compatible !== '' || $license !== '')
        <dl class="pf-product-specs">
          @if ($version !== '')
            <div class="pf-product-spec">
              <dt>{{ __('Version', 'sage') }}</dt>
              <dd>{{ $version }}</dd>
            </div>
          @endif
          @if ($compatible !== '')
            <div class="pf-product-spec">
              <dt>{{ __('Requires', 'sage') }}</dt>
              <dd>{{ $compatible }}</dd>
            </div>
          @endif
          @if ($license !== '')
            <div class="pf-product-spec">
              <dt>{{ __('License', 'sage') }}</dt>
              <dd>{{ $license }}</dd>
            </div>
          @endif
        </dl>
      @endif

      <p class="pf-product-card__help">
        <a href="{{ esc_url($helpUrl) }}">
          {{ $isService ? __('Need a different scope? Get help →', 'sage') : __('Questions? Get help →', 'sage') }}
        </a>
      </p>

      @php
        $jumpLinks = [];
        if (count($gallerySlides) > 1)          $jumpLinks[] = ['#screenshots', __('Screenshots', 'sage')];
        if ($blocks !== [] && $isTheme)         $jumpLinks[] = ['#blocks',      __('Blocks', 'sage')];
        if ($benefits !== [] || $deliverables !== []) $jumpLinks[] = ['#included', __("What's included", 'sage')];
        $jumpLinks[] = ['#buy', __('Pricing', 'sage')];
        if ($faq !== [])                        $jumpLinks[] = ['#faq',         __('FAQ', 'sage')];
      @endphp
      @if (count($jumpLinks) > 1)
        <nav class="pf-product-hero__jumpnav" aria-label="{{ __('Jump to section', 'sage') }}">
          @foreach ($jumpLinks as [$href, $label])
            <a class="pf-jumpnav__link" href="{{ $href }}">{{ $label }}</a>
          @endforeach
        </nav>
      @endif
    </div>

  </div>
</header>

{{-- ═══════════════════════════════════════════════════════════════════════════
     METRICS STRIP
════════════════════════════════════════════════════════════════════════════ --}}
@if ($metrics !== [])
  <div class="pf-metrics-strip">
    <div class="container wide pf-metrics-strip__inner">
      @foreach ($metrics as $m)
        <div class="pf-metric">
          <span class="pf-metric__value">{{ $m[0] ?? '' }}</span>
          <span class="pf-metric__label">{{ $m[1] ?? '' }}</span>
        </div>
      @endforeach
    </div>
  </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SUMMARY — full product overview (1–4 sentences, SEO body copy)
     Placed before screenshots so visitors have context before seeing images.
════════════════════════════════════════════════════════════════════════════ --}}
@if ($summary !== '' && $summary !== $blurb)
  <section class="pf-section pf-product-summary pf-prose" aria-label="{{ __('Product overview', 'sage') }}">
    <div class="container wide">
      <p class="lead">{{ $summary }}</p>
    </div>
  </section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SCREENSHOTS — full gallery
════════════════════════════════════════════════════════════════════════════ --}}
@if (count($gallerySlides) > 1)
  <section id="screenshots" class="pf-section pf-section--alt pf-product-screenshots" aria-labelledby="product-screenshots-heading">
    <div class="container wide">
      <div class="pf-section-head">
        <p class="eyebrow">{{ __('Screenshots', 'sage') }}</p>
        <h2 id="product-screenshots-heading" class="display-title is-section">
          @if ($isPlugin)
            {{ __('See it in action.', 'sage') }}
          @elseif ($productType === 'app')
            {{ __('Inside the app.', 'sage') }}
          @elseif ($isService)
            {{ __('What this looks like.', 'sage') }}
          @else
            {{ __('Inside the theme.', 'sage') }}
          @endif
        </h2>
      </div>
      <div class="pf-screenshots-grid">
        @foreach ($gallerySlides as $i => $slide)
          <figure class="pf-screenshot">
            <button
              type="button"
              class="pf-screenshot__open"
              data-gallery-open
              data-gallery-index="{{ $i }}"
              aria-label="{{ esc_attr(sprintf(__('Open screenshot %d', 'sage'), $i + 1)) }}"
            >
              <img
                src="{{ esc_url($slide['src']) }}"
                alt="{{ esc_attr($slide['alt']) }}"
                width="1200"
                height="750"
                loading="{{ $i < 2 ? 'eager' : 'lazy' }}"
                decoding="async"
              >
            </button>
            @if ($slide['alt'] !== '')
              <figcaption class="pf-screenshot__cap">{{ $slide['alt'] }}</figcaption>
            @endif
          </figure>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     BENEFITS / KEY FEATURES
════════════════════════════════════════════════════════════════════════════ --}}
@if ($benefits !== [])
  <section class="pf-section pf-section--alt pf-product-benefits" aria-labelledby="product-benefits-heading">
    <div class="container wide">
      <div class="pf-section-head">
        <p class="eyebrow">{{ __('Features', 'sage') }}</p>
        <h2 id="product-benefits-heading" class="display-title is-section">{{ __('What you get.', 'sage') }}</h2>
      </div>
      <ul class="pf-benefits-grid">
        @foreach ($benefits as $b)
          <li class="pf-benefit">
            <span class="pf-benefit__icon" aria-hidden="true">{!! \App\mh_svg_icon('check', 15) !!}</span>
            <span class="pf-benefit__text">{{ $b }}</span>
          </li>
        @endforeach
      </ul>
    </div>
  </section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     THE STORY: Problem → Approach → Result
════════════════════════════════════════════════════════════════════════════ --}}
@if ($challenge !== '' || $approach !== '')
  <section class="pf-section pf-product-story" aria-labelledby="product-story-heading">
    <div class="container wide pf-story-grid">
      @if ($challenge !== '')
        <div class="pf-story-col">
          <p class="eyebrow">{{ __('The problem', 'sage') }}</p>
          <h2 id="product-story-heading" class="display-title is-section">{{ __('Built for this niche.', 'sage') }}</h2>
          <p class="pf-story-body">{{ $challenge }}</p>
        </div>
      @endif
      @if ($approach !== '')
        <div class="pf-story-col">
          <p class="eyebrow">{{ __('How it works', 'sage') }}</p>
          <h2 class="display-title is-section">{{ __('The approach.', 'sage') }}</h2>
          <p class="pf-story-body">{{ $approach }}</p>
        </div>
      @endif
    </div>
  </section>
@endif

@if ($result !== '')
  <section class="pf-section pf-section--alt pf-product-result" aria-labelledby="product-result-heading">
    <div class="container wide">
      <h2 id="product-result-heading" class="display-title is-section">{{ __('What you get.', 'sage') }}</h2>
      <p class="lead pf-result-body">{{ $result }}</p>
    </div>
  </section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     DELIVERABLES + FILES INCLUDED
════════════════════════════════════════════════════════════════════════════ --}}
@if ($deliverables !== [] || $filesIncl !== [])
  <section id="included" class="pf-section pf-product-included" aria-labelledby="product-included-heading">
    <div class="container wide pf-included-grid">

      @if ($deliverables !== [])
        <div class="pf-included-col">
          <h2 id="product-included-heading" class="display-title is-section">
            {{ $isPlugin ? __("What\'s included.", 'sage') : __("What\'s in the pack.", 'sage') }}
          </h2>
          <ul class="pf-checklist">
            @foreach ($deliverables as $d)
              <li class="pf-checklist__item">
                <span aria-hidden="true">{!! \App\mh_svg_icon('check', 13) !!}</span>
                {{ $d }}
              </li>
            @endforeach
          </ul>
        </div>
      @endif

      @if ($filesIncl !== [] || $version !== '' || $compatible !== '' || $license !== '')
        <div class="pf-included-col">
          @if ($filesIncl !== [])
            <h3 class="pf-included-sub">{{ __('Files in the pack', 'sage') }}</h3>
            <ul class="pf-file-list">
              @foreach ($filesIncl as $f)
                <li>{!! \App\mh_svg_icon('file', 13) !!} {{ $f }}</li>
              @endforeach
            </ul>
          @endif

          @if ($version !== '' || $compatible !== '' || $license !== '')
            <dl class="pf-release-meta">
              @if ($version !== '')
                <div class="pf-release-meta__row">
                  <dt>{{ __('Version', 'sage') }}</dt>
                  <dd>{{ $version }}</dd>
                </div>
              @endif
              @if ($compatible !== '')
                <div class="pf-release-meta__row">
                  <dt>{{ __('Requires', 'sage') }}</dt>
                  <dd>{{ $compatible }}</dd>
                </div>
              @endif
              @if ($license !== '')
                <div class="pf-release-meta__row">
                  <dt>{{ __('License', 'sage') }}</dt>
                  <dd>{{ $license }}</dd>
                </div>
              @endif
            </dl>
          @endif
        </div>
      @endif

    </div>
  </section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     MID-PAGE NUDGE — customize / hire callout (between features and purchase)
════════════════════════════════════════════════════════════════════════════ --}}
<div class="pf-section pf-product-hire-nudge" aria-label="{{ __('Custom work', 'sage') }}">
  <div class="container wide">
    <div class="pf-hire-nudge">
      <div class="pf-hire-nudge__copy">
        <p class="eyebrow">{{ __('Need it customized?', 'sage') }}</p>
        <p class="pf-hire-nudge__text">
          @if ($isService)
            {{ __('This pack has a fixed scope. Need something adjacent or a custom quote? Write a short brief and I will price it.', 'sage') }}
          @elseif ($isPlugin)
            {{ __('Buy the plugin as-is, or hire me to extend it for your stack. I scope custom builds from a short brief.', 'sage') }}
          @else
            {{ __('Buy the pack for a self-serve install, or hire me to brand it, import your content, and hand off wp-admin to your team.', 'sage') }}
          @endif
        </p>
      </div>
      <a class="btn btn-outline pf-hire-nudge__cta" href="{{ esc_url($helpUrl) }}">
        {!! \App\mh_svg_icon('mail', 15) !!} {{ __('Get help', 'sage') }}
      </a>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     GUTENBERG BLOCKS INCLUDED (themes only)
════════════════════════════════════════════════════════════════════════════ --}}
@if ($blocks !== [] && $isTheme)
  <section id="blocks" class="pf-section pf-section--alt pf-product-blocks" aria-labelledby="product-blocks-heading">
    <div class="container wide">
      <div class="pf-section-head">
        <p class="eyebrow">{{ __('Built-in blocks', 'sage') }}</p>
        <h2 id="product-blocks-heading" class="display-title is-section">
          {{ sprintf(__('%d Gutenberg blocks included.', 'sage'), count($blocks)) }}
        </h2>
        <p class="pf-blocks-intro">{{ __('Every block is a real Core Gutenberg block — live preview in the editor, no page builder required.', 'sage') }}</p>
      </div>
      <ul class="pf-blocks-grid" aria-label="{{ __('Included blocks', 'sage') }}">
        @foreach ($blocks as $block)
          @php
            $blockName = is_array($block) ? (string) ($block[0] ?? '') : (string) $block;
            $blockIcon = is_array($block) ? (string) ($block[1] ?? 'block') : 'block';
          @endphp
          @if ($blockName !== '')
            <li class="pf-block-card">
              <span class="pf-block-card__icon" aria-hidden="true">{!! \App\mh_svg_icon($blockIcon, 32) !!}</span>
              <span class="pf-block-card__name">{{ $blockName }}</span>
            </li>
          @endif
        @endforeach
      </ul>
    </div>
  </section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     WHO IT IS FOR
════════════════════════════════════════════════════════════════════════════ --}}
@if ($audience !== '')
  <section class="pf-section pf-section--alt pf-product-audience" aria-labelledby="product-audience-heading">
    <div class="container wide">
      <p class="eyebrow">{{ __('Audience', 'sage') }}</p>
      <h2 id="product-audience-heading" class="display-title is-section">{{ __('Who it is for.', 'sage') }}</h2>
      <p class="lead">{{ $audience }}</p>
    </div>
  </section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     PURCHASE (WooCommerce cart)
════════════════════════════════════════════════════════════════════════════ --}}
<section class="pf-section pf-product-purchase" aria-labelledby="product-buy-heading" id="buy">
  <div class="container wide">

    <div class="pf-purchase-layout">

      <div class="pf-purchase-copy">
        <p class="eyebrow">{{ __('Get it now', 'sage') }}</p>
        <h2 id="product-buy-heading" class="display-title is-section">
          {{ $isFree
            ? sprintf(__('Install %s — it\'s free.', 'sage'), $productTitle)
            : sprintf(__('Get %s.', 'sage'), $productTitle) }}
        </h2>
        <p class="lead">
          @if ($isService)
            {{ __('Checkout books this pack as scoped. Need a custom version? Get help and I will quote it.', 'sage') }}
          @elseif ($isFree)
            {{ $isPlugin
              ? __('Free download. Activate under Plugins → Installed Plugins, or copy the zip from GitHub Releases.', 'sage')
              : __('Free download. Activate under Appearance → Themes, or copy the zip from GitHub Releases.', 'sage') }}
          @else
            {{ __('Instant digital download after checkout. Need it branded and installed? Get help and I\'ll ship the full build.', 'sage') }}
          @endif
        </p>

        @if ($deliverables !== [])
          <ul class="pf-purchase-includes">
            @foreach (array_slice($deliverables, 0, 5) as $d)
              <li>{!! \App\mh_svg_icon('check', 13) !!} {{ $d }}</li>
            @endforeach
            @if (count($deliverables) > 5)
              <li class="pf-purchase-includes__more">
                {{ sprintf(__('+ %d more items', 'sage'), count($deliverables) - 5) }}
              </li>
            @endif
          </ul>
        @endif
      </div>

      <div class="pf-purchase-widget">
        @php do_action('woocommerce_before_main_content'); @endphp
        @while (have_posts())
          @php
            the_post();
            wc_get_template_part('content', 'single-product');
          @endphp
        @endwhile
        @php do_action('woocommerce_after_main_content'); @endphp

        <p class="pf-purchase-widget__help">
          {{ __('Questions about fit or customization?', 'sage') }}
          <a href="{{ esc_url($helpUrl) }}">{{ __('Get help →', 'sage') }}</a>
        </p>
      </div>

    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
     TECHNICAL — stack, architecture, handoff
════════════════════════════════════════════════════════════════════════════ --}}
@if ($architecture !== '' || $handoff !== '' || $tech !== [])
  <section class="pf-section pf-section--alt pf-product-technical" aria-labelledby="product-tech-heading">
    <div class="container wide">
      <p class="eyebrow">{{ __('Stack &amp; code', 'sage') }}</p>
      <h2 id="product-tech-heading" class="display-title is-section">{{ __('How it is built.', 'sage') }}</h2>

      @if ($tech !== [])
        <div class="pf-tech-tags">
          @foreach ($tech as $t)
            <span class="pf-tech-tag">{{ $t }}</span>
          @endforeach
        </div>
      @endif

      @if ($architecture !== '')
        <p class="pf-technical-body">{{ $architecture }}</p>
      @endif

      @if ($handoff !== '')
        <h3 class="pf-technical-sub">{{ __('Handoff', 'sage') }}</h3>
        <p class="pf-technical-body">{{ $handoff }}</p>
      @endif
    </div>
  </section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     DOCS + GITHUB
════════════════════════════════════════════════════════════════════════════ --}}
@if ($docs !== [] || $githubUrl !== '' || $support !== '')
  <section class="pf-section pf-product-docs" aria-labelledby="product-docs-heading">
    <div class="container wide pf-docs-layout">
      <div>
        <h2 id="product-docs-heading" class="display-title is-section">{{ __('Documentation.', 'sage') }}</h2>
        @if ($docs !== [])
          <ul class="pf-docs-list">
            @foreach ($docs as $doc)
              @php $docLabel = (string) ($doc[0] ?? ''); $docUrl = (string) ($doc[1] ?? ''); @endphp
              @if ($docLabel !== '' && $docUrl !== '')
                <li>
                  <a href="{{ esc_url($docUrl) }}" target="_blank" rel="noopener">
                    {!! \App\mh_svg_icon('file', 14) !!}
                    {{ $docLabel }}
                    <span aria-hidden="true">↗</span>
                  </a>
                </li>
              @endif
            @endforeach
          </ul>
        @endif
      </div>

      @if ($githubUrl !== '' || $support !== '')
        <div class="pf-docs-links">
          @if ($githubUrl !== '')
            <a class="pf-docs-ext-link" href="{{ esc_url($githubUrl) }}" target="_blank" rel="noopener">
              {!! \App\mh_svg_icon('github', 16) !!}
              {{ __('View source on GitHub', 'sage') }}
              <span aria-hidden="true">↗</span>
            </a>
          @endif
          @if ($support !== '')
            <a class="pf-docs-ext-link" href="{{ esc_url($support) }}" target="_blank" rel="noopener">
              {!! \App\mh_svg_icon('mail', 16) !!}
              {{ __('Support guide', 'sage') }}
              <span aria-hidden="true">↗</span>
            </a>
          @endif
        </div>
      @endif
    </div>
  </section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     FAQ
════════════════════════════════════════════════════════════════════════════ --}}
@if ($faq !== [])
  <section class="pf-section pf-section--alt pf-product-faq" aria-labelledby="product-faq-heading" id="faq">
    <div class="container wide pf-faq-layout">
      <div class="pf-faq-aside">
        <p class="eyebrow">{{ __('Questions', 'sage') }}</p>
        <h2 id="product-faq-heading" class="display-title is-section">{{ __('Common questions.', 'sage') }}</h2>
        <p class="pf-faq-aside__sub">{{ __('Question not answered here?', 'sage') }}</p>
        <a class="btn btn--sm" href="{{ esc_url($helpUrl) }}">
          {!! \App\mh_svg_icon('mail', 14) !!} {{ __('Ask me directly', 'sage') }}
        </a>
      </div>
      <div class="pf-faq-list">
        @foreach ($faq as $i => $f)
          <details class="pf-faq-item" {{ $i === 0 ? 'open' : '' }}>
            <summary class="pf-faq-item__q">{{ $f[0] ?? '' }}</summary>
            <p class="pf-faq-item__a">{{ $f[1] ?? '' }}</p>
          </details>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     RELATED PRODUCTS / UPSELLS — pulls from WooCommerce linked products
════════════════════════════════════════════════════════════════════════════ --}}
@php
  $relatedIds = function_exists('wc_get_related_products') ? wc_get_related_products($productId, 3) : [];
  $upsellIds  = $wcProduct ? array_slice($wcProduct->get_upsell_ids(), 0, 3) : [];
  $relatedShow = array_filter(array_unique(array_merge($upsellIds, $relatedIds)));
  $relatedShow = array_slice(array_values($relatedShow), 0, 3);
@endphp
@if (! empty($relatedShow))
  <section class="pf-section pf-section--alt pf-product-related" aria-labelledby="product-related-heading">
    <div class="container wide">
      <div class="pf-section-head">
        <p class="eyebrow">{{ __('Also in the catalog', 'sage') }}</p>
        <h2 id="product-related-heading" class="display-title is-section">{{ __('You might also like.', 'sage') }}</h2>
      </div>
      <div class="pf-related-grid">
        @foreach ($relatedShow as $relId)
          @php
            $relWcp   = function_exists('wc_get_product') ? wc_get_product($relId) : null;
            $relEntry = \App\mh_product_catalog_data((int) $relId);
            if (! $relWcp) continue;
            $relTitle = html_entity_decode($relWcp->get_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $relBlurb = trim((string) ($relEntry['blurb'] ?? ($relEntry['summary'] ?? '')));
            $relPrice = $relWcp->get_price_html();
            $relUrl   = (string) get_permalink($relId);
            $relThumb = has_post_thumbnail($relId)
              ? wp_get_attachment_image_url((int) get_post_thumbnail_id($relId), 'medium')
              : '';
            $relType  = \App\mh_resolve_product_type((int) $relId);
            $relChrome = \App\mh_product_type_chrome($relType);
            if ($relThumb === '') {
              $relSlides = \App\mh_product_gallery_slides((int) $relId, $relEntry, $relWcp);
              $relThumb = (string) ($relSlides[0]['src'] ?? '');
            }
          @endphp
          <article class="pf-related-card">
            <a class="pf-related-card__img-wrap{{ $relThumb === '' ? ' is-empty' : '' }}" href="{{ esc_url($relUrl) }}" tabindex="-1" aria-hidden="true">
              @if ($relThumb !== '')
                <img
                  src="{{ esc_url($relThumb) }}"
                  alt="{{ esc_attr(sprintf(__('%s preview', 'sage'), $relTitle)) }}"
                  width="480" height="300"
                  loading="lazy" decoding="async"
                >
              @else
                {!! \App\mh_product_fallback_markup((int) $relId, $relTitle, $relType) !!}
              @endif
            </a>
            <div class="pf-related-card__body">
              <p class="pf-related-card__type eyebrow">{{ $relChrome['short'] }}</p>
              <h3 class="pf-related-card__title">
                <a href="{{ esc_url($relUrl) }}">{{ $relTitle }}</a>
              </h3>
              @if ($relBlurb !== '')
                <p class="pf-related-card__blurb">{{ Str::limit($relBlurb, 100) }}</p>
              @endif
              <div class="pf-related-card__footer">
                @if ($relPrice !== '')
                  <span class="pf-related-card__price">{!! wp_kses_post($relPrice) !!}</span>
                @endif
                <a class="btn btn--sm" href="{{ esc_url($relUrl) }}">{{ __('View', 'sage') }}</a>
              </div>
            </div>
          </article>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     BOTTOM CTA BAND
════════════════════════════════════════════════════════════════════════════ --}}
@include('partials.cta-band', [
  'kicker'        => __('From this product', 'sage'),
  'title'         => $isFree
    ? sprintf(__('Install %s today — it\'s free.', 'sage'), $productTitle)
    : sprintf(__('Ready for %s?', 'sage'), $productTitle),
  'text'          => $isService
    ? __('Book the pack as scoped, or write and I will quote a custom version.', 'sage')
    : ($isPlugin
      ? __('Download, activate, done. Or hire me to extend it for your stack.', 'sage')
      : __('Buy the pack for a self-serve install, or hire me to brand it, import your inventory, and hand off wp-admin to your team.', 'sage')),
  'label'         => $primaryLabel,
  'href'          => $buyUrl ?: $helpUrl,
  'secondary'     => __('Browse all products', 'sage'),
  'secondaryHref' => $projectsUrl,
])

{{-- ═══════════════════════════════════════════════════════════════════════════
     STICKY BUY BAR — appears when hero purchase card scrolls out of view
════════════════════════════════════════════════════════════════════════════ --}}
@if ($buyUrl !== '' || $demoUrl !== '')
  <div
    class="pf-sticky-bar"
    id="pf-sticky-bar"
    aria-hidden="true"
    data-trigger=".pf-product-buybox"
  >
    <div class="container wide pf-sticky-bar__inner">
      <div class="pf-sticky-bar__info">
        <span class="pf-sticky-bar__title">{{ $productTitle }}</span>
        @if (! $isFree && $priceHtml !== '')
          <span class="pf-sticky-bar__price">{!! wp_kses_post($priceHtml) !!}</span>
        @elseif ($isFree)
          <span class="pf-sticky-bar__price">{{ __('Free', 'sage') }}</span>
        @endif
      </div>
      <div class="pf-sticky-bar__actions">
        @if ($demoUrl !== '')
          <a class="btn btn-outline btn--sm pf-sticky-bar__demo" href="{{ esc_url($demoUrl) }}" target="_blank" rel="noopener">
            {{ __('Live demo', 'sage') }}
          </a>
        @endif
        @if ($buyUrl !== '')
          <a class="btn btn--sm pf-sticky-bar__buy" href="{{ esc_url($buyUrl) }}">
            {!! \App\mh_svg_icon($isPlugin ? 'download' : 'cart', 14) !!}
            {{ $primaryLabel }}
          </a>
        @endif
      </div>
    </div>
  </div>
@endif

@if ($gallerySlides !== [])
  <script type="application/json" id="pf-gallery-data">{!! json_encode(
    $gallerySlides,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
  ) !!}</script>
  <dialog class="pf-lightbox" data-product-lightbox aria-labelledby="pf-lightbox-caption">
    <div class="pf-lightbox__frame">
      <button type="button" class="pf-lightbox__close" data-lightbox-close aria-label="{{ __('Close screenshot', 'sage') }}">
        <span aria-hidden="true">×</span>
      </button>
      <button type="button" class="pf-lightbox__nav pf-lightbox__nav--prev" data-lightbox-prev aria-label="{{ __('Previous screenshot', 'sage') }}">
        <span aria-hidden="true">‹</span>
      </button>
      <figure class="pf-lightbox__figure">
        <img src="" alt="" width="1600" height="1000" data-lightbox-image>
        <figcaption class="pf-lightbox__caption" id="pf-lightbox-caption" data-lightbox-caption></figcaption>
      </figure>
      <button type="button" class="pf-lightbox__nav pf-lightbox__nav--next" data-lightbox-next aria-label="{{ __('Next screenshot', 'sage') }}">
        <span aria-hidden="true">›</span>
      </button>
    </div>
  </dialog>
@endif

@php do_action('get_footer', 'shop'); @endphp
@endsection
