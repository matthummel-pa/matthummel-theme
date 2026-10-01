{{--
  Template Name: Now
--}}
@extends('layouts.app')

@php
  $gh      = \App\Github::fetchUser(\App\mh_github_login());
  $ghUrl   = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $updated = \App\mh_now_updated();
@endphp

@section('content')

{{-- ── HERO ─────────────────────────────────────────────── --}}
@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('now_kicker', __('Now', 'sage')) }}</p>
  <h1 class="display-title is-hero">{{ \App\field('now_h1', __('What I’m doing now.', 'sage')) }}</h1>
  <p class="lead">{{ \App\field('now_lede', __('A short list of where my time is going. GitHub, the journal, and the profiles I keep are below.', 'sage')) }}</p>
  <div class="page-header-split__actions">
    <a class="btn" href="{{ esc_url(home_url('/contact/')) }}">
      {!! \App\mh_svg_icon('mail', 15) !!} {{ __('Say hello', 'sage') }}
    </a>
    <a class="h-text-arrow" href="{{ esc_url(home_url('/about/')) }}">
      {{ __('Full background', 'sage') }} <span aria-hidden="true">→</span>
    </a>
  </div>
@endcomponent

@php
  $nowPills = [
    ['activity', __('Right now', 'sage')],
    ['studio', __('Studio', 'sage')],
  ];
  if (\App\mh_is_hireable($gh)) {
    $nowPills[] = ['availability', __('Open for work', 'sage')];
  }
  $nowPills[] = ['writing', __('Writing', 'sage')];
  $nowPills[] = ['ai', __('How I work', 'sage')];
  $nowPills[] = ['life', __('Life', 'sage')];
  $nowPills[] = ['list', __('Short list', 'sage')];
  $nowPills[] = ['note', __('Send a note', 'sage')];
@endphp
@include('partials.page-nav', ['pills' => $nowPills])

{{-- ── MAIN CONTENT + SIDEBAR ─────────────────────────── --}}
<section class="pf-section" aria-label="{{ esc_attr__('Current focus', 'sage') }}">
  <div class="container wide now-layout">

    @include('partials.now-activity', ['ghUrl' => $ghUrl])

    <div class="now-main">

      {{-- Studio work --}}
      <article class="now-block" id="studio">
        <div class="now-block__head">
          <div class="now-block__icon" aria-hidden="true">{!! \App\mh_svg_icon('briefcase', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">{{ __('Studio work', 'sage') }}</p>
            <h2 class="now-block__title">{{ __('Matt Hummel', 'sage') }}</h2>
          </div>
        </div>
        <p>{{ \App\field('now_studio_p1', __('I publish WordPress concepts here — themes and plugins that show how I build. Hire me for a production site or a role.', 'sage')) }}</p>
        <p>{!! \App\field_html('now_studio_p2', __('Browse the <a href="/projects/">Work page</a>. When you\'re ready for a custom build, say hello.', 'sage')) !!}</p>
        <a class="h-text-arrow" href="{{ esc_url(home_url('/projects/')) }}">
          {{ __('See the Work page', 'sage') }} <span aria-hidden="true">→</span>
        </a>
      </article>

      {{-- Open for work (GitHub hireable) --}}
      @if (\App\mh_is_hireable($gh))
      <article class="now-block" id="availability">
        <div class="now-block__head">
          <div class="now-block__icon" aria-hidden="true">{!! \App\mh_svg_icon('check', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">{{ __('Availability', 'sage') }}</p>
            <h2 class="now-block__title">{{ \App\mh_availability_label($gh, __('Open for new work', 'sage')) }}</h2>
          </div>
        </div>
        <p>{{ \App\field('now_work_p1', __('I\'m actively looking for full-time roles, contract work, freelance projects, and agency partnerships. My focus is full-stack web development, especially WordPress, PHP, JavaScript, React, and API integrations.', 'sage')) }}</p>
        <p>{{ \App\field('now_work_p2', __('If you\'re hiring a full-stack developer, need WordPress expertise, or want a dependable development partner for overflow work, a short note is enough to start.', 'sage')) }}</p>
        <div class="now-actions">
          <a class="btn" href="{{ esc_url(home_url('/contact/')) }}">{!! \App\mh_svg_icon('mail', 15) !!} {{ __('Say hello', 'sage') }}</a>
          <a class="h-text-arrow" href="{{ esc_url(home_url('/hire/')) }}">{{ __('See hire details', 'sage') }} <span aria-hidden="true">→</span></a>
        </div>
      </article>
      @endif

      {{-- Writing --}}
      <article class="now-block" id="writing">
        <div class="now-block__head">
          <div class="now-block__icon" aria-hidden="true">{!! \App\mh_svg_icon('pen', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">{{ __('Writing', 'sage') }}</p>
            <h2 class="now-block__title">{{ __('Notes from real builds', 'sage') }}</h2>
          </div>
        </div>
        <p>{{ \App\field('now_write_p1', __('I write short posts on WordPress, PHP, and the tools I actually use on projects. Most posts include code you can paste into a theme or plugin. I write for developers who want something working, not a tutorial that ends at "and so on."', 'sage')) }}</p>
        <p>{{ \App\field('now_write_p2', __('Posts go on the journal first. Some get cross-posted to DEV.to. Nothing is paywalled.', 'sage')) }}</p>
        <a class="h-text-arrow" href="{{ esc_url($writing) }}">{{ __('Read the journal', 'sage') }} <span aria-hidden="true">→</span></a>
      </article>

      {{-- AI and tooling --}}
      <article class="now-block" id="ai">
        <div class="now-block__head">
          <div class="now-block__icon" aria-hidden="true">{!! \App\mh_svg_icon('code', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">{{ __('How I work', 'sage') }}</p>
            <h2 class="now-block__title">{{ __('Building with AI, reviewing every line', 'sage') }}</h2>
          </div>
        </div>
        <p>{{ \App\field('now_ai_p1', __('I use Cursor AI, Claude, and ChatGPT as part of my development workflow. AI makes the first pass faster — I review everything before it ships. The final code is something I can explain and maintain.', 'sage')) }}</p>
        <p>{{ \App\field('now_ai_p2', __('I\'m honest about this because I think it matters: if you hire me, you\'re getting real engineering judgment, not just generated output. This site was planned and built with Cursor AI.', 'sage')) }}</p>
        <a class="h-text-arrow" href="{{ esc_url(home_url('/uses/')) }}">{{ __('See the full stack', 'sage') }} <span aria-hidden="true">→</span></a>
      </article>

      {{-- Life --}}
      <article class="now-block" id="life">
        <div class="now-block__head">
          <div class="now-block__icon" aria-hidden="true">{!! \App\mh_svg_icon('users', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">{{ __('Life', 'sage') }}</p>
            <h2 class="now-block__title">{{ __('Family and focus', 'sage') }}</h2>
          </div>
        </div>
        <p>{{ \App\field('now_life_p1', __('I live with my family. Nights and weekends belong to people, not projects. Weekdays I take full-time, contract, and freelance WordPress work. I work Eastern Time hours.', 'sage')) }}</p>
      </article>

      {{-- The short list --}}
      <article class="now-block now-block--list" id="list">
        <div class="now-block__head">
          <div class="now-block__icon" aria-hidden="true">{!! \App\mh_svg_icon('check', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">{{ __('The short version', 'sage') }}</p>
            <h2 class="now-block__title">{{ __('Right now, in one list', 'sage') }}</h2>
          </div>
        </div>
        <ul class="now-checklist">
          @foreach (\App\field_lines('now_items', [
            __('Open for full-time, contract, and freelance WordPress / full-stack work.', 'sage'),
            __('Shipping WordPress concepts from studio projects (Work page).', 'sage'),
            __('Writing short posts on WordPress development — code you can paste in', 'sage'),
            __('Using Cursor AI and Claude to build faster, reviewing every line before it ships', 'sage'),
            __('Raising kids — nights and weekends stay with family. Weekdays I take hireable work.', 'sage'),
            __('Working Eastern Time, available for remote and local clients', 'sage'),
          ]) as $item)
            <li>{!! \App\mh_svg_icon('check', 14) !!}<span>{{ $item }}</span></li>
          @endforeach
        </ul>
      </article>

    </div>

    {{-- ── SIDEBAR ─────────────────────────────────────── --}}
    <aside class="now-sidebar" aria-label="{{ esc_attr__('Status and details', 'sage') }}">

      @if (\App\mh_is_hireable($gh))
      <div class="now-sidebar-card now-sidebar-card--status">
        <div class="now-sidebar-status-dot">
          @include('partials.avail-mark', ['gh' => $gh])
          <span class="now-sidebar-card__label">{{ __('Status', 'sage') }}</span>
        </div>
        <p class="now-sidebar-card__value">{{ \App\mh_availability_label($gh, __('Open for work', 'sage')) }}</p>
        <ul class="now-sidebar-card__list">
          <li>{!! \App\mh_svg_icon('check', 12) !!} {{ __('Full-time roles', 'sage') }}</li>
          <li>{!! \App\mh_svg_icon('check', 12) !!} {{ __('Contract / freelance', 'sage') }}</li>
          <li>{!! \App\mh_svg_icon('check', 12) !!} {{ __('Agency overflow', 'sage') }}</li>
          <li>{!! \App\mh_svg_icon('check', 12) !!} {{ __('Remote anywhere', 'sage') }}</li>
        </ul>
        <a class="btn now-sidebar-card__cta" href="{{ esc_url(home_url('/contact/')) }}">
          {!! \App\mh_svg_icon('mail', 14) !!} {{ __('Say hello', 'sage') }}
        </a>
      </div>
      @endif

      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">{{ __('Last updated', 'sage') }}</p>
        @if ($updated['iso'] !== '')
          <p class="now-sidebar-card__value"><time datetime="{{ esc_attr($updated['iso']) }}">{{ $updated['label'] }}</time></p>
        @else
          <p class="now-sidebar-card__value">{{ $updated['label'] }}</p>
        @endif
      </div>

      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">{{ __('Location', 'sage') }}</p>
        <p class="now-sidebar-card__value">{{ __('Eastern Time', 'sage') }}</p>
        <p class="now-sidebar-card__sub">{{ __('Eastern Time (ET) · Remote friendly', 'sage') }}</p>
      </div>

      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">{{ __('Primary stack', 'sage') }}</p>
        <p class="now-sidebar-card__value">{{ __('WordPress + PHP', 'sage') }}</p>
        <p class="now-sidebar-card__sub">{{ __('PHP, JavaScript, React, APIs', 'sage') }}</p>
        <a class="now-sidebar-card__link" href="{{ esc_url(home_url('/uses/')) }}">{{ __('Full stack', 'sage') }} <span aria-hidden="true">→</span></a>
      </div>

      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">{{ __('Studio work', 'sage') }}</p>
        <p class="now-sidebar-card__value">{{ __('Matt Hummel', 'sage') }}</p>
        <p class="now-sidebar-card__sub">{{ __('WordPress themes & plugins', 'sage') }}</p>
        <a class="now-sidebar-card__link" href="{{ esc_url(\App\mh_work_listing_url()) }}">{{ __('See the work', 'sage') }} <span aria-hidden="true">→</span></a>
      </div>

      @if (! empty($gh['public_repos']))
      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">{{ __('GitHub', 'sage') }}</p>
        <p class="now-sidebar-card__value">{{ sprintf(
          /* translators: %s: number of public repositories */
          _n('%s public repo', '%s public repos', (int) $gh['public_repos'], 'sage'),
          number_format_i18n((int) $gh['public_repos'])
        ) }}</p>
        @if (! empty($gh['followers']))
          <p class="now-sidebar-card__sub">{{ sprintf(
            /* translators: %s: number of GitHub followers */
            _n('%s follower', '%s followers', (int) $gh['followers'], 'sage'),
            number_format_i18n((int) $gh['followers'])
          ) }}</p>
        @endif
        <a class="now-sidebar-card__link" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">{{ __('View profile', 'sage') }} <span aria-hidden="true">→</span></a>
      </div>
      @endif

    </aside>

    @include('partials.now-note')

  </div>
</section>

{{-- ── CTA ─────────────────────────────────────────────── --}}
<section class="cta-band" aria-labelledby="now-cta-heading" data-reveal>
  <div class="container wide cta-band-inner">
    <div class="cta-band__copy">
      <p class="eyebrow eyebrow--on-dark">{{ __('Let’s work together', 'sage') }}</p>
      <h2 id="now-cta-heading" class="display-title is-section">{{ __('Something I can help with?', 'sage') }}</h2>
      <p>{{ __('A short note is enough to start.', 'sage') }} {{ \App\mh_reply_sla() }}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn-on-dark" href="{{ esc_url(home_url('/contact/')) }}">
        {!! \App\mh_svg_icon('mail', 16) !!} {{ __('Say hello', 'sage') }}
      </a>
      <p class="cta-band__note">{{ \App\mh_reply_sla('note') }}</p>
    </div>
  </div>
</section>

@endsection
