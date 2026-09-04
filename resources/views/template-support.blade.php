{{--
  Template Name: Support
  HTML documentation hub for themes and plugins (ThemeForest-style guides).
--}}
@extends('layouts.app')

@php
  $products = \App\mh_support_hub_products();
  $hireUrl = home_url('/hire/');
  $contactUrl = home_url('/contact/');
@endphp

@section('content')

@component('partials.page-hero', ['extra' => 'page-header--support', 'split' => true, 'asideLabel' => __('Docs snapshot', 'sage')])
  <p class="eyebrow">{{ \App\field('support_kicker', __('Support', 'sage')) }}</p>
  <h1 class="display-title is-hero">{{ \App\field('support_h1', __('Theme & plugin documentation.', 'sage')) }}</h1>
  <p class="lead">{{ \App\field('support_lede', __('HTML guides for products I sell — the same Documentation hub that ships in the ThemeForest-style pack. Open a page in the browser; GitHub issues stay for reproducible bugs.', 'sage')) }}</p>
  <div class="page-header-split__actions">
    <a class="btn" href="{{ esc_url($contactUrl) }}">{{ \App\field('support_contact_label', __('Say hello', 'sage')) }}</a>
    <a class="h-text-arrow" href="{{ home_url('/projects/') }}">
      {{ __('Browse products', 'sage') }} <span aria-hidden="true">→</span>
    </a>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/support',
      'icon' => 'wordpress',
      'title' => __('HTML documentation', 'sage'),
      'meta' => __('Open in browser · same as pack', 'sage'),
      'stats' => [
        ['value' => number_format_i18n(count($products)), 'label' => __('Products', 'sage')],
        ['value' => __('HTML', 'sage'), 'label' => __('Viewable guides', 'sage')],
        ['value' => __('GitHub', 'sage'), 'label' => __('Source docs', 'sage')],
        ['value' => __('GPL', 'sage'), 'label' => __('Theme license', 'sage')],
      ],
      'link' => [
        'label' => __('Open Acreline hub', 'sage'),
        'href' => \App\mh_product_html_docs_hub_url('acreline'),
        'external' => true,
      ],
    ])
  @endslot
@endcomponent

<section class="pf-section work-guide" aria-labelledby="support-intro-heading">
  <div class="container wide">
    <h2 id="support-intro-heading" class="display-title is-section">
      {{ \App\field('support_intro_h2', __('How this works.', 'sage')) }}
    </h2>
    <p class="lead work-guide__intro">{{ \App\field('support_intro_p', __('Each product links to viewable HTML docs (install, Customizer, FAQ, support) hosted from the product repo. Pack buyers also get the same files offline under Documentation/. Contact me for paid install or customization.', 'sage')) }}</p>
  </div>
</section>

<div class="support-body pf-section">
  <div class="container wide support-body__stack">
    @forelse ($products as $product)
      <section class="support-product" id="{{ esc_attr($product['slug']) }}" aria-labelledby="support-{{ esc_attr($product['slug']) }}-title">
        <header class="support-product__head">
          <div class="support-product__titles">
            <p class="support-product__eyebrow">
              {{ $product['type'] === 'plugin' ? __('WordPress plugin', 'sage') : __('WordPress theme', 'sage') }}
              @if ($product['version'] !== '')
                <span aria-hidden="true">·</span> v{{ $product['version'] }}
              @endif
            </p>
            <h2 id="support-{{ esc_attr($product['slug']) }}-title" class="support-product__title">{{ $product['title'] }}</h2>
            @if ($product['blurb'] !== '')
              <p class="support-product__blurb">{{ $product['blurb'] }}</p>
            @endif
          </div>
          <div class="support-product__actions">
            @if ($product['hub'] !== '')
              <a class="btn" href="{{ esc_url($product['hub']) }}" rel="noopener" target="_blank">
                {{ __('Open HTML docs', 'sage') }} <span aria-hidden="true">↗</span>
              </a>
            @endif
            <a class="btn btn-outline" href="{{ esc_url($product['project_url']) }}">{{ __('Product page', 'sage') }}</a>
            @if ($product['demo'] !== '')
              <a class="h-text-arrow" href="{{ esc_url($product['demo']) }}" rel="noopener" target="_blank">
                {{ __('Live demo', 'sage') }} <span aria-hidden="true">↗</span>
              </a>
            @endif
          </div>
        </header>

        @if ($product['guides'] !== [])
          <h3 class="support-product__sub">{{ __('HTML guide', 'sage') }}</h3>
          <ul class="support-guide-grid">
            @foreach ($product['guides'] as $guide)
              <li>
                <a class="support-guide-card" href="{{ esc_url($guide['url']) }}" rel="noopener" target="_blank">
                  <span class="support-guide-card__title">{{ $guide['label'] }} <span aria-hidden="true">↗</span></span>
                  @if (! empty($guide['blurb']))
                    <span class="support-guide-card__blurb">{{ $guide['blurb'] }}</span>
                  @endif
                </a>
              </li>
            @endforeach
          </ul>
        @elseif ($product['docs'] !== [])
          <h3 class="support-product__sub">{{ __('Documentation', 'sage') }}</h3>
          <ul class="support-doc-list">
            @foreach ($product['docs'] as $doc)
              <li>
                <a href="{{ esc_url($doc['url']) }}" rel="noopener" target="_blank">{{ $doc['label'] }} <span aria-hidden="true">↗</span></a>
              </li>
            @endforeach
          </ul>
        @endif

        <p class="support-product__meta">
          @if ($product['github'] !== '')
            <a href="{{ esc_url($product['github']) }}" rel="noopener" target="_blank">{!! \App\mh_svg_icon('github', 14) !!} {{ __('Repository', 'sage') }}</a>
          @endif
          @if ($product['support'] !== '')
            <a href="{{ esc_url($product['support']) }}" rel="noopener" target="_blank">{{ __('Support notes', 'sage') }} ↗</a>
          @endif
          @if ($product['github'] !== '')
            <a href="{{ esc_url(rtrim($product['github'], '/').'/issues') }}" rel="noopener" target="_blank">{{ __('GitHub issues', 'sage') }} ↗</a>
          @endif
        </p>
      </section>
    @empty
      <p class="lead">{{ __('Product documentation will appear here when sellable themes or plugins are listed in the catalog.', 'sage') }}</p>
    @endforelse

    <aside class="support-help" aria-labelledby="support-help-heading">
      <h2 id="support-help-heading" class="display-title is-section">{{ __('Need a hand beyond the docs?', 'sage') }}</h2>
      <p class="lead">{{ __('Paid install, branding, or inventory import is available. Bug reports with WordPress version, PHP version, and theme version belong on GitHub.', 'sage') }}</p>
      <div class="support-help__actions">
        <a class="btn" href="{{ esc_url($hireUrl) }}">{{ __('Hire me', 'sage') }}</a>
        <a class="btn btn-outline" href="{{ esc_url($contactUrl) }}">{{ __('Contact', 'sage') }}</a>
      </div>
    </aside>
  </div>
</div>

@endsection
