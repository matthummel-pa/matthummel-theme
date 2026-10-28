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
  $groups = [];
  foreach ($types as $key => $type) {
    if (in_array($key, $present, true) || $key === $scope) {
      $tabs[] = [$key, $type['plural'], home_url('/'.$type['prefix'].'/')];
    }
    $rows = array_values(array_filter($items, static fn ($i) => $i['type'] === $key));
    if ($rows !== []) {
      $groups[$key] = $rows;
    }
  }
  $defaults = [
    'all' => [__('Themes and plugins for WordPress', 'sage'), __('WordPress themes and plugins I build, sell, and support. Browse by type, open a live demo, and buy a yearly license through Freemius. Plugins are free to download.', 'sage')],
    'theme' => [__('WordPress themes', 'sage'), __('Block themes I build and support. Each one has a live demo where there is one, a yearly license for updates and email support, and a 30-day refund if I cannot fix a problem.', 'sage')],
    'plugin' => [__('Free WordPress plugins', 'sage'), __('Plugins I build and maintain. They are free: download the zip from the plugin page.', 'sage')],
  ][$scope] ?? [
    $types[$scope]['plural'],
    sprintf(__('Browse the %s I offer.', 'sage'), strtolower($types[$scope]['plural'])),
  ];
  $faq = [];
  foreach ([1, 2, 3, 4] as $n) {
    $q = trim(\App\field('shop_faq_q'.$n, '', $pageId));
    $a = trim(\App\field('shop_faq_a'.$n, '', $pageId));
    if ($q !== '' && $a !== '') {
      $faq[] = [$q, $a];
    }
  }
  $how = trim(\App\field_html('shop_how', '', $pageId));
@endphp

@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('shop_kicker', __('Shop', 'sage'), $pageId) }}</p>
  <h1 class="display-title is-hero">{{ \App\field('shop_h1', $defaults[0], $pageId) }}</h1>
  <p class="lead">{{ \App\field('shop_lede', $defaults[1], $pageId) }}</p>
@endcomponent

<section class="project-section" aria-labelledby="shop-browse-heading">
  <div class="container wide">
    <h2 id="shop-browse-heading" class="visually-hidden">{{ __('Browse products', 'sage') }}</h2>

    {{-- Plain links, no script: each view is its own page, so it works without JavaScript and can be shared. --}}
    <nav aria-label="{{ __('Product types', 'sage') }}">
      <ul class="concept-hero-actions" style="list-style:none;padding:0;margin:0 0 1.5rem">
        @foreach ($tabs as [$key, $label, $url])
          <li>
            <a class="btn{{ $key === $scope ? '' : ' btn-outline' }}" href="{{ esc_url($url) }}"@if ($key === $scope) aria-current="page"@endif>{{ $label }}</a>
          </li>
        @endforeach
      </ul>
    </nav>

    <p class="project-section__intro">
      {{ sprintf(_n('%d product', '%d products', count($items), 'sage'), count($items)) }}
    </p>

    @if ($items === [])
      <p>{{ \App\field('shop_empty', __('Nothing is listed here yet. Say hello if you are looking for something specific.', 'sage'), $pageId) }}</p>
    @endif

    @foreach ($groups as $key => $rows)
      <h2 class="display-title is-section">{{ $types[$key]['plural'] }}</h2>
      <div class="work-grid">
        @foreach ($rows as $item)
          @include('partials.shop-card', ['item' => $item])
        @endforeach
      </div>
    @endforeach
  </div>
</section>

@if ($how !== '')
  <section class="project-section" aria-labelledby="shop-how-heading">
    <div class="container wide">
      <h2 id="shop-how-heading" class="display-title is-section">{{ \App\field('shop_how_h2', __('How buying works', 'sage'), $pageId) }}</h2>
      {!! wp_kses_post($how) !!}
    </div>
  </section>
@endif

@if ($faq !== [])
  <section class="project-section" aria-labelledby="shop-faq-heading">
    <div class="container wide">
      <h2 id="shop-faq-heading" class="display-title is-section">{{ \App\field('shop_faq_h2', __('Questions', 'sage'), $pageId) }}</h2>
      @foreach ($faq as [$question, $answer])
        <h3>{{ $question }}</h3>
        <p>{{ $answer }}</p>
      @endforeach
      <p class="project-section__intro">
        {{ __('Something else?', 'sage') }}
        <a href="{{ esc_url(home_url('/contact/')) }}">{{ __('Say hello', 'sage') }}</a>
      </p>
    </div>
  </section>
@endif
@endsection
