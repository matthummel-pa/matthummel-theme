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
@endphp

@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('shop_kicker', __('Shop', 'sage'), $pageId) }}</p>
  <h1 class="display-title is-hero">{{ \App\field('shop_'.$scope.'_h1', \App\mh_shop_copy_default($scope, 'h1'), $pageId) }}</h1>
  <p class="lead">{{ \App\field('shop_'.$scope.'_lede', \App\mh_shop_copy_default($scope, 'lede'), $pageId) }}</p>
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
      <h2 id="shop-how-heading" class="display-title is-section">{{ \App\field($howKey.'_h2', \App\mh_shop_copy_default($scope, 'how_h2'), $pageId) }}</h2>
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
