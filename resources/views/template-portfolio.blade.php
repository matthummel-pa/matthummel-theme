{{--
  Template Name: Portfolio
  Portfolio page — lists all public GitHub repos and codebase.
  Replaces the project CPT as the code / GitHub listing surface.
--}}
@extends('layouts.app')

@section('content')
@php
  $postId       = (int) get_the_ID();
  $ghLogin      = \App\mh_github_login();
  $ghProfile    = \App\mh_github_profile();
  $featured     = \App\mh_code_page_repos($postId);
  $live         = \App\mh_code_page_live_repos(12, $postId);
  $totalRepos   = (int) ($ghProfile['public_repos'] ?? 0);
  $followers    = (int) ($ghProfile['followers'] ?? 0);
  $ghUrl        = 'https://github.com/'.$ghLogin;
  $ghStars      = \App\mh_github_star_total();

  // Products for the mini-showcase strip.
  $portfolioProducts = [];
  if (\App\mh_shop_ready() && function_exists('wc_get_products')) {
    $pids = wc_get_products(['limit' => 3, 'status' => 'publish', 'return' => 'ids']);
    foreach ($pids as $pid) {
      $payload = \App\mh_shop_product_payload((int) $pid);
      if (! $payload) {
        continue;
      }
      $entry = \App\mh_product_entry((int) $pid);
      $portfolioProducts[] = array_merge($entry, [
        'price_html' => $payload['price_html'],
        'permalink'  => $payload['permalink'],
        'is_free'    => $payload['is_free'],
      ]);
    }
  }
@endphp

{{-- HERO --}}
@component('partials.page-hero', ['split' => true, 'asideLabel' => __('GitHub snapshot', 'sage')])
  <p class="eyebrow">{{ \App\field('portfolio_kicker', __('Portfolio', 'sage'), $postId) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('portfolio_h1', __('WordPress and full-stack code on GitHub.', 'sage'), $postId) }}
  </h1>
  <p class="lead">
    {{ \App\field('portfolio_lede', __('All public repos — Sage themes, WordPress plugins, React apps, and spec builds you can fork, study, and use. Some power themes and plugins you can buy in the shop.', 'sage'), $postId) }}
  </p>
  <div class="page-header-split__actions">
    <a class="btn" href="{{ home_url('/shop/') }}">
      {!! \App\mh_svg_icon('briefcase', 16) !!} {{ __('Browse products', 'sage') }}
    </a>
    <a class="h-text-arrow" href="{{ esc_url($ghUrl) }}" target="_blank" rel="noopener">
      {{ __('GitHub profile', 'sage') }} <span aria-hidden="true">→</span>
    </a>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'github.com/'.$ghLogin,
      'icon'   => 'github',
      'title'  => __('Public repos', 'sage'),
      'meta'   => __('WordPress, PHP, React, TypeScript', 'sage'),
      'stats'  => [
        ['value' => $totalRepos > 0 ? number_format_i18n($totalRepos) : count($featured) + count($live), 'label' => __('Public repos', 'sage')],
        ['value' => $followers > 0 ? number_format_i18n($followers) : '—', 'label' => __('Followers', 'sage')],
        ['value' => $ghStars > 0 ? number_format_i18n($ghStars) : '—', 'label' => __('Stars earned', 'sage')],
        ['value' => 'PHP · JS', 'label' => __('Primary languages', 'sage')],
      ],
      'link' => ['label' => __('Open GitHub', 'sage'), 'href' => $ghUrl],
    ])
  @endslot
@endcomponent

{{-- INTRO --}}
<section class="pf-section" aria-labelledby="portfolio-intro-heading">
  <div class="container wide">
    <p class="eyebrow">{{ __('What is here', 'sage') }}</p>
    <h2 id="portfolio-intro-heading" class="display-title is-section">
      {{ \App\field('portfolio_intro_h2', __('Code I ship publicly.', 'sage'), $postId) }}
    </h2>
    <p class="lead">
      {{ \App\field('portfolio_intro_p', __('Most production work lived inside employers. What is here are Sage WordPress themes, WordPress plugins, and full-stack apps I have published since 2025. Fork anything, copy any snippet — a note if you ship something with it is kind, not required.', 'sage'), $postId) }}
    </p>
  </div>
</section>

{{-- PRODUCTS FOR SALE --}}
@if ($portfolioProducts !== [])
  <section class="pf-section pf-section--alt portfolio-products" aria-labelledby="portfolio-products-heading">
    <div class="container wide page-block">
      <p class="eyebrow">{{ __('For sale', 'sage') }}</p>
      <h2 id="portfolio-products-heading" class="display-title is-section">
        {{ \App\field('portfolio_products_h2', __('Themes and plugins built from these repos.', 'sage'), $postId) }}
      </h2>
      <p class="lead" style="margin-bottom:2rem">
        {{ \App\field('portfolio_products_intro', __('The public repos are the codebase behind these products. Buy a pack and get the finished theme or plugin — no setup from scratch required.', 'sage'), $postId) }}
      </p>
      <div class="portfolio-product-grid">
        @foreach ($portfolioProducts as $p)
          @php
            $pName  = (string) ($p['name'] ?? $p['title'] ?? '');
            $pBlurb = (string) ($p['blurb'] ?? $p['summary'] ?? '');
            $pLink  = (string) ($p['permalink'] ?? '');
            $pTech  = (array)  ($p['tech'] ?? []);
          @endphp
          @if ($pName !== '' && $pLink !== '')
            <article class="portfolio-product-card">
              <div class="portfolio-product-card__copy">
                <h3 class="portfolio-product-card__title">{{ $pName }}</h3>
                @if ($pBlurb !== '')
                  <p class="portfolio-product-card__blurb">{{ $pBlurb }}</p>
                @endif
                @if ($pTech !== [])
                  <p class="portfolio-product-card__tech">{{ implode(' · ', array_slice($pTech, 0, 4)) }}</p>
                @endif
              </div>
              <div class="portfolio-product-card__foot">
                @if (! ($p['is_free'] ?? false))
                  <span class="portfolio-product-card__price">{!! $p['price_html'] !!}</span>
                @endif
                <a class="btn btn--sm" href="{{ esc_url($pLink) }}">{{ __('View product', 'sage') }}</a>
              </div>
            </article>
          @endif
        @endforeach
      </div>
      <p style="margin-top:1.5rem">
        <a class="h-text-arrow" href="{{ home_url('/shop/') }}">{{ __('Browse all products', 'sage') }} <span aria-hidden="true">→</span></a>
      </p>
    </div>
  </section>
@endif

{{-- FEATURED REPOS --}}
@if ($featured !== [])
  <section class="pf-section pf-section--alt" aria-labelledby="portfolio-featured-heading">
    <div class="container wide page-block">
      <p class="eyebrow">{{ __('Featured', 'sage') }}</p>
      <h2 id="portfolio-featured-heading" class="display-title is-section">
        {{ \App\field('portfolio_feat_h2', __('Repos worth starting with.', 'sage'), $postId) }}
      </h2>
      <p class="lead" style="margin-bottom:2rem">
        {{ \App\field('portfolio_feat_intro', __('Sage themes, WordPress plugins, and web apps — each one built to be read, forked, or hired from. Stack notes and live demos where available.', 'sage'), $postId) }}
      </p>
      <div class="repo-grid" data-code-grid>
        @foreach ($featured as $i => $r)
          @include('partials.repo-card', ['r' => $r, 'index' => $i + 1, 'featured' => true, 'variant' => 'featured'])
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- RECENTLY PUSHED --}}
@if ($live !== [])
  <section class="pf-section" aria-labelledby="portfolio-live-heading">
    <div class="container wide page-block">
      <p class="eyebrow">{{ __('Active', 'sage') }}</p>
      <h2 id="portfolio-live-heading" class="display-title is-section">
        {{ \App\field('portfolio_live_h2', __('Recently pushed.', 'sage'), $postId) }}
      </h2>
      <p class="lead" style="margin-bottom:2rem">
        {{ \App\field('portfolio_live_intro', __('Fresh commits on public repos — what I am shipping this week. These pull live from the GitHub API.', 'sage'), $postId) }}
      </p>
      <div class="repo-grid repo-grid--live" data-code-grid>
        @foreach ($live as $r)
          @include('partials.repo-card', ['r' => $r, 'variant' => 'live'])
        @endforeach
      </div>
      <p class="archive-desc" style="margin-top:1.5rem">
        <a class="h-text-arrow" href="{{ esc_url($ghUrl) }}" target="_blank" rel="noopener">
          {{ \App\field('portfolio_all_label', __('Browse all public repos on GitHub', 'sage'), $postId) }} <span aria-hidden="true">→</span>
        </a>
      </p>
    </div>
  </section>
@endif

{{-- WHAT I BUILD / SKILLS INTRO --}}
<section class="pf-section pf-section--alt" aria-labelledby="portfolio-practice-heading">
  <div class="container wide">
    <p class="eyebrow">{{ __('Stack', 'sage') }}</p>
    <h2 id="portfolio-practice-heading" class="display-title is-section">
      {{ \App\field('portfolio_practice_h2', __('What you will find in the repos.', 'sage'), $postId) }}
    </h2>
    <div class="work-guide__prose">
      <p>{{ \App\field('portfolio_practice_p', __('The public repos cover Sage 11 WordPress themes, WordPress plugins (Gutenberg blocks, PHP class libraries), full-stack React and TypeScript apps, and developer tooling. Code is documented at handoff quality — comments explain intent, not syntax.', 'sage'), $postId) }}</p>
    </div>
    @php $practiceGroups = \App\mh_code_page_practice_grouped($postId); @endphp
    @if ($practiceGroups !== [])
      <div class="code-practice-groups" style="margin-top:2rem">
        @foreach ($practiceGroups as $group)
          <div class="code-practice-group">
            <h3 class="code-practice-group__label">
              {!! \App\mh_svg_icon($group['icon'], 16) !!} {{ $group['label'] }}
            </h3>
            <ul class="code-practice-list">
              @foreach ($group['items'] as $item)
                <li title="{{ $item['body'] }}">{{ $item['title'] }}</li>
              @endforeach
            </ul>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</section>

{{-- COMMUNITY / FOLLOWERS --}}
@php
  $ghFollowers = \App\mh_github_followers(12);
@endphp
@if ($ghFollowers !== [])
  <section class="pf-section" aria-labelledby="portfolio-community-heading">
    <div class="container wide">
      <h2 id="portfolio-community-heading" class="display-title is-section">
        {{ \App\field('portfolio_community_h2', __('People who follow and star my repos.', 'sage'), $postId) }}
      </h2>
      <p>{{ \App\field('portfolio_community_p', __('Thank you for reading the code, starring a repo, or following along. Every star and follower matters.', 'sage'), $postId) }}</p>
      <div class="code-followers-grid" style="margin-top:1.5rem">
        @foreach ($ghFollowers as $f)
          <a class="code-follower" href="{{ esc_url($f['url']) }}" target="_blank" rel="noopener" title="{{ esc_attr($f['name']) }}">
            @if ($f['avatar'] !== '')
              <img
                src="{{ esc_url($f['avatar']) }}"
                alt="{{ esc_attr($f['name']) }}"
                width="40"
                height="40"
                loading="lazy"
                class="code-follower__avatar"
              >
            @else
              <span class="code-follower__initials">{{ substr($f['name'], 0, 1) }}</span>
            @endif
          </a>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- CTA --}}
@include('partials.cta-band', [
  'kicker'        => __('Work together', 'sage'),
  'title'         => \App\field('portfolio_cta_h2', __('Buy a theme, fork a repo, or say hello.', 'sage'), $postId),
  'text'          => \App\field('portfolio_cta_lede', __('Browse the shop for ready-to-buy WordPress themes and plugins. Fork any repo on GitHub, or write if you want to work together.', 'sage'), $postId),
  'label'         => \App\field('portfolio_cta_btn', __('Browse products', 'sage'), $postId),
  'href'          => home_url('/shop/'),
  'secondary'     => __('Say hello', 'sage'),
  'secondaryHref' => home_url('/contact/'),
])
@endsection
