{{--
  Template Name: Shop
  Product listing for the store. One template, three pages: /shop/ (everything), /themes/ and /plugins/.
  The page slug picks the scope. Checkout lives on each product page (Freemius overlay), not here.
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
  $howKey = $scope === 'plugin' ? 'shop_plugin_how' : 'shop_how';
  $how = trim(\App\field_html($howKey, \App\mh_shop_how_default($scope), $pageId));

  $showCompare = $scope !== 'plugin' && count($themes) >= 2;
  $heroTarget = $available !== [] ? '#shop-available' : ($soonItems !== [] ? '#shop-soon' : '');
  $pills = array_values(array_filter([
    $available !== [] ? ['shop-available', $copy('avail_h2')] : null,
    $soonItems !== [] ? ['shop-soon', $copy('soon_h2')] : null,
    $showCompare ? ['shop-compare', $copy('compare_h2')] : null,
    $how !== '' ? ['shop-how', \App\field($howKey.'_h2', \App\mh_shop_copy_default($scope, 'how_h2'), $pageId)] : null,
    $faq !== [] ? ['shop-faq', \App\field('shop_faq_h2', __('Questions', 'sage'), $pageId)] : null,
  ]));
  $ldJson = \App\mh_shop_jsonld($items, $faq, $h1, $lede);
  $shopUrl = home_url('/shop/');
@endphp

@component('partials.page-hero')
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
    <div class="shop-trust" aria-label="{{ __('What you can count on', 'sage') }}">{!! wp_kses_post($trust) !!}</div>
  @endif
@endcomponent

@include('partials.page-nav', ['pills' => $pills])

<section class="project-section shop-section" aria-labelledby="shop-browse-heading">
  <div class="container wide">
    <h2 id="shop-browse-heading" class="visually-hidden">{{ __('Browse products', 'sage') }}</h2>

    {{-- Plain links, no script: each view is its own page, so it works without JavaScript and can be shared. --}}
    <nav class="shop-tabs" aria-label="{{ __('Product types', 'sage') }}">
      <ul>
        @foreach ($tabs as [$key, $label, $url])
          <li>
            <a class="btn{{ $key === $scope ? '' : ' btn-outline' }}" href="{{ esc_url($url) }}"@if ($key === $scope) aria-current="page"@endif>{{ $label }}</a>
          </li>
        @endforeach
      </ul>
      <p class="shop-tabs__count">{{ sprintf(_n('%d product', '%d products', count($items), 'sage'), count($items)) }}</p>
    </nav>

    @if ($items === [])
      <p>{{ \App\field('shop_empty', __('Nothing is listed here yet. Say hello if you are looking for something specific.', 'sage'), $pageId) }}</p>
    @endif

    @if ($available !== [])
      <div class="shop-group" id="shop-available" aria-labelledby="shop-available-heading">
        <h2 id="shop-available-heading" class="display-title is-section">{{ $copy('avail_h2') }}</h2>
        <p class="project-section__intro">{{ $copy('avail_intro') }}</p>
        <div class="work-grid shop-grid">
          @foreach ($available as $item)
            @include('partials.shop-card', ['item' => $item])
          @endforeach
        </div>
      </div>
    @endif

    @if ($soonItems !== [])
      <div class="shop-group shop-group--soon" id="shop-soon" aria-labelledby="shop-soon-heading">
        <h2 id="shop-soon-heading" class="display-title is-section">{{ $copy('soon_h2') }}</h2>
        <p class="project-section__intro">
          {{ $copy('soon_intro') }}
          <a href="{{ esc_url(home_url('/get-updates/')) }}">{{ __('Get updates', 'sage') }}</a>
        </p>
        <div class="work-grid shop-grid">
          @foreach ($soonItems as $item)
            @include('partials.shop-card', ['item' => $item])
          @endforeach
        </div>
      </div>
    @endif
  </div>
</section>

@if ($showCompare)
  <section class="project-section shop-section" id="shop-compare" aria-labelledby="shop-compare-heading">
    <div class="container wide">
      <h2 id="shop-compare-heading" class="display-title is-section">{{ $copy('compare_h2') }}</h2>
      <p class="project-section__intro">{{ $copy('compare_intro') }}</p>
      @include('partials.shop-compare', ['themes' => $themes, 'labelId' => 'shop-compare-heading'])
    </div>
  </section>
@endif

@if ($how !== '')
  <section class="project-section shop-section" id="shop-how" aria-labelledby="shop-how-heading">
    <div class="container wide">
      <h2 id="shop-how-heading" class="display-title is-section">{{ \App\field($howKey.'_h2', \App\mh_shop_copy_default($scope, 'how_h2'), $pageId) }}</h2>
      <div class="shop-steps">{!! wp_kses_post($how) !!}</div>
    </div>
  </section>
@endif

@if ($faq !== [])
  <section class="project-section shop-section" id="shop-faq" aria-labelledby="shop-faq-heading">
    <div class="container wide">
      <h2 id="shop-faq-heading" class="display-title is-section">{{ \App\field('shop_faq_h2', __('Questions', 'sage'), $pageId) }}</h2>
      <div class="shop-faq">
        @foreach ($faq as [$question, $answer])
          <div class="shop-faq__item">
            <h3>{{ $question }}</h3>
            <p>{{ $answer }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endif

<section class="project-section shop-section shop-cta" id="shop-cta" aria-labelledby="shop-cta-heading">
  <div class="container wide">
    <h2 id="shop-cta-heading" class="display-title is-section">{{ $copy('cta_h2') }}</h2>
    <p class="project-section__intro">{{ $copy('cta_text') }}</p>
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
