{{--
  Template Name: Shop
  Product listing for the store. One template, three pages: /shop/ (everything), /themes/ and /plugins/.
  The page slug picks the scope. Checkout lives on each product page (Freemius overlay), not here.
  Section drawings reuse partials/section-illustration (the animated blue/green line art from the other pages).
--}}
@extends('layouts.app')

@section('content')
@php
  $pageId = (int) get_the_ID();
  $slug = (string) get_post_field('post_name', $pageId);
  $types = \App\mh_product_types();
  $scope = 'all';
  foreach ($types as $key => $type) {
    if ($slug === $type['prefix']) {
      $scope = $key;
    }
  }
  $allItems = \App\mh_shop_items('all');
  $items = $scope === 'all' ? $allItems : array_values(array_filter($allItems, static fn ($i) => $i['type'] === $scope));
  $present = array_unique(array_column($allItems, 'type'));
  // A type gets a tab once it has something listed (or when you are already on its page).
  $tabs = [['all', __('All products', 'sage'), home_url('/shop/')]];
  foreach ($types as $key => $type) {
    if (in_array($key, $present, true) || $key === $scope) {
      $tabs[] = [$key, $type['plural'], home_url('/'.$type['prefix'].'/')];
    }
  }
  $available = array_values(array_filter($items, static fn ($i) => $i['status'] !== 'soon'));
  $soonItems = array_values(array_filter($items, static fn ($i) => $i['status'] === 'soon'));
  $themes = array_values(array_filter($items, static fn ($i) => $i['type'] === 'theme'));

  $h1 = \App\field('shop_'.$scope.'_h1', \App\mh_shop_copy_default($scope, 'h1'), $pageId);
  $lede = \App\field('shop_'.$scope.'_lede', \App\mh_shop_copy_default($scope, 'lede'), $pageId);
  $primaryLabel = \App\field('shop_'.$scope.'_primary', \App\mh_shop_copy_default($scope, 'primary'), $pageId);
  $copy = static fn (string $part): string => \App\field('shop_'.$part, \App\mh_shop_copy_default($scope, $part), $pageId);
  $trust = trim(\App\field_html($scope === 'plugin' ? 'shop_plugin_trust' : 'shop_trust', \App\mh_shop_trust_default($scope), $pageId));

  // The plugins page has its own short set; every other page uses the theme questions.
  $faqKey = $scope === 'plugin' ? 'shop_plugin_faq' : 'shop_faq';
  $faqDefaults = \App\mh_shop_faq_defaults($scope);
  $faq = [];
  foreach ($faqDefaults as $i => [$defaultQ, $defaultA]) {
    $q = trim(\App\field($faqKey.'_q'.($i + 1), $defaultQ, $pageId));
    $a = trim(\App\field($faqKey.'_a'.($i + 1), $defaultA, $pageId));
    if ($q !== '' && $a !== '') {
      $faq[] = [$q, $a];
    }
  }
  $faqIntro = \App\field($scope === 'plugin' ? 'shop_plugin_faq_intro' : 'shop_all_faq_intro', \App\mh_shop_copy_default($scope, 'faq_intro'), $pageId);
  $howKey = $scope === 'plugin' ? 'shop_plugin_how' : 'shop_how';
  $how = trim(\App\field_html($howKey, \App\mh_shop_how_default($scope), $pageId));
  $howHeading = \App\field($howKey.'_h2', \App\mh_shop_copy_default($scope, 'how_h2'), $pageId);

  // Docs to read first (from the catalog) and the share links (the same helper the journal posts use).
  $docGroups = [];
  foreach ($items as $item) {
    if ($item['docs'] !== []) {
      $docGroups[] = [$item['title'], $item['docs']];
    }
  }
  $share = \App\mh_post_share_urls($pageId, $h1);

  $showCompare = $scope !== 'plugin' && count($themes) >= 2;
  $heroTarget = $available !== [] ? '#shop-available' : ($soonItems !== [] ? '#shop-soon' : '');
  $pills = array_values(array_filter([
    $available !== [] ? ['shop-available', $copy('avail_h2')] : null,
    $soonItems !== [] ? ['shop-soon', $copy('soon_h2')] : null,
    $showCompare ? ['shop-compare', $copy('compare_h2')] : null,
    $how !== '' ? ['shop-how', $howHeading] : null,
    $faq !== [] ? ['questions', $copy('faq_h2')] : null,
    ['shop-share', $copy('share_eyebrow')],
  ]));
  $ldJson = \App\mh_shop_jsonld($items, $faq, $h1, $lede);
  $shopUrl = home_url('/shop/');
@endphp

@component('partials.page-hero', ['extra' => 'shop-hero'])
  <nav class="shop-crumbs" aria-label="{{ __('Breadcrumb', 'sage') }}">
    <ol>
      <li><a href="{{ esc_url(home_url('/')) }}">{{ __('Home', 'sage') }}</a></li>
      @if ($scope === 'all')
        <li aria-current="page">{{ __('Shop', 'sage') }}</li>
      @else
        <li><a href="{{ esc_url($shopUrl) }}">{{ __('Shop', 'sage') }}</a></li>
        <li aria-current="page">{{ $types[$scope]['plural'] }}</li>
      @endif
    </ol>
  </nav>
  <p class="eyebrow">{{ \App\field('shop_kicker', __('Shop', 'sage'), $pageId) }}</p>
  <h1 class="display-title is-hero">{{ $h1 }}</h1>
  <p class="lead">{{ $lede }}</p>
  @if ($heroTarget !== '')
    <p class="concept-hero-actions">
      <a class="btn" href="{{ $heroTarget }}">{{ $primaryLabel }} <span aria-hidden="true">↓</span></a>
    </p>
  @endif
  @if ($trust !== '')
    <div class="shop-trust" role="group" aria-label="{{ esc_attr__('What you can count on', 'sage') }}">{!! wp_kses_post($trust) !!}</div>
  @endif
@endcomponent

@include('partials.page-nav', ['pills' => $pills])

{{-- The type tabs sit right above the first product cards. Only when nothing is listed do they get a section of their own. --}}
@if ($items === [])
  <section class="project-section shop-section shop-section--toolbar" aria-label="{{ __('Browse products', 'sage') }}">
    <div class="container wide">
      @include('partials.shop-tabs', ['tabs' => $tabs, 'scope' => $scope, 'count' => 0])
      <p>{{ \App\field('shop_empty', __('Nothing is listed here yet. Say hello if you are looking for something specific.', 'sage'), $pageId) }}</p>
    </div>
  </section>
@endif

@if ($available !== [])
  <section class="project-section shop-section mh-block-section--illus" id="shop-available" aria-labelledby="shop-available-heading">
    @include('partials.section-illustration', ['anchor' => 'work'])
    <div class="container wide">
      <p class="eyebrow">{{ $copy('avail_eyebrow') }}</p>
      <h2 id="shop-available-heading" class="display-title is-section">{{ $copy('avail_h2') }}</h2>
      <p class="shop-section__intro">{{ $copy('avail_intro') }}</p>
      {{-- Count what is on sale under "Available now", not the coming-soon cards listed further down. --}}
      @include('partials.shop-tabs', ['tabs' => $tabs, 'scope' => $scope, 'count' => count($available)])
      <div class="work-grid shop-grid">
        @foreach ($available as $item)
          @include('partials.shop-card', ['item' => $item])
        @endforeach
      </div>
    </div>
  </section>
@endif

@if ($soonItems !== [])
  <section class="project-section shop-section shop-section--soon mh-block-section--illus" id="shop-soon" aria-labelledby="shop-soon-heading">
    @include('partials.section-illustration', ['anchor' => 'now'])
    <div class="container wide">
      <p class="eyebrow">{{ $copy('soon_eyebrow') }}</p>
      <h2 id="shop-soon-heading" class="display-title is-section">{{ $copy('soon_h2') }}</h2>
      <p class="shop-section__intro">
        {{ $copy('soon_intro') }}
        <a href="{{ esc_url(home_url('/get-updates/')) }}">{{ __('Get updates', 'sage') }}</a>
      </p>
      @if ($available === [])
        @include('partials.shop-tabs', ['tabs' => $tabs, 'scope' => $scope, 'count' => count($items)])
      @endif
      <div class="work-grid shop-grid">
        @foreach ($soonItems as $item)
          @include('partials.shop-card', ['item' => $item])
        @endforeach
      </div>
    </div>
  </section>
@endif

@if ($showCompare)
  <section class="project-section shop-section mh-block-section--illus" id="shop-compare" aria-labelledby="shop-compare-heading">
    @include('partials.section-illustration', ['anchor' => 'uses'])
    <div class="container wide">
      <p class="eyebrow">{{ $copy('compare_eyebrow') }}</p>
      <h2 id="shop-compare-heading" class="display-title is-section">{{ $copy('compare_h2') }}</h2>
      <p class="shop-section__intro">{{ $copy('compare_intro') }}</p>
      @include('partials.shop-compare', ['themes' => $themes, 'labelId' => 'shop-compare-heading'])
    </div>
  </section>
@endif

@if ($how !== '')
  <section class="project-section shop-section mh-block-section--illus" id="shop-how" aria-labelledby="shop-how-heading">
    @include('partials.section-illustration', ['anchor' => 'newsletter'])
    <div class="container wide">
      <p class="eyebrow">{{ $copy('how_eyebrow') }}</p>
      <h2 id="shop-how-heading" class="display-title is-section">{{ $howHeading }}</h2>
      <div class="shop-steps">{!! wp_kses_post($how) !!}</div>
    </div>
  </section>
@endif

@if ($faq !== [])
  {{-- Same two-column layout as the Home FAQ (id="questions" brings its styles): heading, drawing and help card on
       the left, the accordion on the right. --}}
  <section class="project-section shop-section shop-section--faq" id="questions" aria-labelledby="shop-faq-heading">
    <div class="container wide">
      <div class="faq-layout shop-faq-layout">
        <div class="faq-aside">
          <p class="eyebrow">{{ $copy('faq_eyebrow') }}</p>
          <h2 id="shop-faq-heading" class="display-title is-section">{{ $copy('faq_h2') }}</h2>
          <p>{{ $faqIntro }}</p>
          @include('partials.section-illustration', ['anchor' => 'questions'])
          <div class="faq-help-card">
            <h3>{{ $copy('help_h3') }}</h3>
            <p>{{ $copy('help_text') }}</p>
            <p class="faq-help-card__actions">
              <a class="btn" href="{{ esc_url(home_url('/contact/')) }}">
                {!! \App\mh_svg_icon('mail', 15) !!}
                {{ $copy('help_label') }}
              </a>
              <a class="shop-link" href="{{ esc_url('mailto:hello@matthummel.com') }}">hello@matthummel.com</a>
            </p>
          </div>
        </div>
        <div class="faq-main">
          <div class="faq-list">
            @foreach ($faq as $i => [$question, $answer])
              <details @if ($i === 0) open @endif>
                <summary>{{ $question }}</summary>
                <p>{{ $answer }}</p>
              </details>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </section>
@endif

<section class="project-section shop-section mh-block-section--illus" id="shop-share" aria-labelledby="shop-share-heading">
  @include('partials.section-illustration', ['anchor' => 'journal-posts'])
  <div class="container wide">
    <p class="eyebrow">{{ $copy('share_eyebrow') }}</p>
    <h2 id="shop-share-heading" class="display-title is-section">{{ $copy('share_h2') }}</h2>
    <p class="shop-section__intro">{{ $copy('share_intro') }}</p>
    @include('partials.shop-share', [
      'docGroups' => $docGroups,
      'share' => $share,
      'docsHeading' => $copy('docs_h3'),
      'shareHeading' => $copy('share_h3'),
    ])
  </div>
</section>

<section class="project-section shop-section shop-cta mh-block-section--illus" id="shop-cta" aria-labelledby="shop-cta-heading">
  @include('partials.section-illustration', ['anchor' => 'write'])
  <div class="container wide">
    <p class="eyebrow">{{ $copy('cta_eyebrow') }}</p>
    <h2 id="shop-cta-heading" class="display-title is-section">{{ $copy('cta_h2') }}</h2>
    <p class="shop-section__intro">{{ $copy('cta_text') }}</p>
    <p class="concept-hero-actions">
      <a class="btn" href="{{ esc_url(home_url('/contact/')) }}">
        {!! \App\mh_svg_icon('mail', 15) !!}
        {{ $copy('cta_label') }}
      </a>
    </p>
  </div>
</section>

@if ($ldJson !== '')
  <script type="application/ld+json">{!! $ldJson !!}</script>
@endif
@endsection
