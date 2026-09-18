{{--
  Template Name: Support
  HTML documentation hub for themes and plugins (ThemeForest-style guides).
--}}
@extends('layouts.app')

@php
  $products = \App\mh_support_hub_products();
  $contactUrl = home_url('/contact/');
@endphp

@section('content')

@component('partials.page-hero', ['extra' => 'page-header--support', 'split' => true, 'asideLabel' => __('Docs snapshot', 'sage')])
  <p class="eyebrow">{{ \App\field('support_kicker', __('Support', 'sage')) }}</p>
  <h1 class="display-title is-hero">{{ \App\field('support_h1', __('Theme & plugin documentation.', 'sage')) }}</h1>
  <p class="lead">{{ \App\field('support_lede', __('HTML guides for the themes and plugins I publish. Open a page in the browser; GitHub issues stay for reproducible bugs.', 'sage')) }}</p>
  <div class="page-header-split__actions">
    <a class="btn" href="{{ esc_url($contactUrl) }}">{{ \App\field('support_contact_label', __('Say hello', 'sage')) }}</a>
    <a class="h-text-arrow" href="{{ esc_url(\App\mh_work_listing_url()) }}">
      {{ __('See the work', 'sage') }} <span aria-hidden="true">→</span>
    </a>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/support',
      'icon' => 'wordpress',
      'title' => __('HTML documentation', 'sage'),
      'meta' => __('Open in browser · same as pack', 'sage'),
      'link' => [
        'label' => __('Open Acreline hub', 'sage'),
        'href' => \App\mh_product_html_docs_hub_url('acreline'),
        'external' => true,
      ],
    ])
  @endslot
@endcomponent

@php
  $supportPills = [
    ['support-intro-heading', __('How it works', 'sage')],
  ];
  foreach ($products as $product) {
    $supportPills[] = [$product['slug'], $product['title']];
  }
@endphp
@include('partials.page-nav', ['pills' => $supportPills])

<section class="pf-section work-guide" aria-labelledby="support-intro-heading">
  <div class="container wide">
    <h2 id="support-intro-heading" class="display-title is-section">
      {{ \App\field('support_intro_h2', __('How this works.', 'sage')) }}
    </h2>
    <p class="lead work-guide__intro">{{ \App\field('support_intro_p', __('Each concept links to viewable HTML docs (install, Customizer, FAQ) hosted from the repo. Contact me if you want one installed or customized.', 'sage')) }}</p>
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
            <a class="btn btn-outline" href="{{ esc_url($product['project_url']) }}">{{ __('Concept page', 'sage') }}</a>
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
      <p class="lead">{{ __('Documentation will appear here when themes or plugins are listed.', 'sage') }}</p>
    @endforelse

    <aside class="support-help" aria-labelledby="support-help-heading">
      <h2 id="support-help-heading" class="display-title is-section">{{ __('Need a hand beyond the docs?', 'sage') }}</h2>
      <p class="lead">{{ __('Need install, branding, or a custom build? Write. Bug reports with WordPress version, PHP version, and theme version belong on GitHub.', 'sage') }}</p>
      <div class="support-help__actions">
        <a class="btn" href="{{ esc_url($contactUrl) }}">{{ __('Say hello', 'sage') }}</a>
      </div>
    </aside>
  </div>
</div>

@endsection
