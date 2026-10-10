{{--
  Template Name: Resources
  Free starters, themes, disclosed tool recommendations, and the stack I use (Uses folded in, 3.6.50).
--}}
@extends('layouts.app')

@php
  $sections = \App\mh_resources_catalog();
  $stack = \App\mh_uses_sections();
  $disclosureUrl = \App\mh_affiliate_disclosure_url();
  $hasAffiliate = false;
  foreach ($sections as $section) {
    foreach ($section['items'] as $item) {
      if (! empty($item['affiliate'])) {
        $hasAffiliate = true;
      }
    }
  }
  foreach ($stack as $group) {
    foreach ($group['items'] as $row) {
      if (! empty($row[3])) {
        $hasAffiliate = true;
      }
    }
  }

  $resourcePills = [
    ['resources-intro-heading', __('Overview', 'sage')],
  ];
  foreach ($sections as $section) {
    $resourcePills[] = ['resources-'.\Illuminate\Support\Str::slug($section['title']), $section['title']];
  }
  $resourcePills[] = ['uses', __('Stack I use', 'sage')];
@endphp

@section('content')

@component('partials.page-hero', ['extra' => 'page-header--resources'])
  <p class="eyebrow">{{ \App\field('resources_kicker', __('Resources', 'sage')) }}</p>
  <h1 class="display-title is-hero">{{ \App\field('resources_h1', __('Free starters, themes, tools, and the stack I use.', 'sage')) }}</h1>
  <p class="lead">{{ \App\field('resources_lede', __('A quiet catalog for developers and shops: open code to study, tools I use on real projects, and the stack behind every build. Hire me when you want a full site.', 'sage')) }}</p>
  <div class="page-header-split__actions">
    <a class="btn" href="{{ home_url('/contact/') }}">{{ __('Say hello', 'sage') }}</a>
    <a class="h-text-arrow" href="#uses">
      {{ __('Jump to the stack', 'sage') }} <span aria-hidden="true">→</span>
    </a>
  </div>
@endcomponent

@include('partials.page-nav', ['pills' => $resourcePills])

@if ($hasAffiliate)
  <div class="container wide">
    <aside class="affiliate-note" role="note" aria-label="{{ __('Affiliate disclosure', 'sage') }}">
      <p>
        <strong>{{ __('Affiliate disclosure:', 'sage') }}</strong>
        {{ \App\mh_affiliate_disclosure_note() }}
        <a href="{{ esc_url($disclosureUrl) }}">{{ __('How affiliate links work', 'sage') }}</a>
      </p>
    </aside>
  </div>
@endif

<section class="pf-section work-guide" aria-labelledby="resources-intro-heading">
  <div class="container wide">
    <h2 id="resources-intro-heading" class="display-title is-section">
      {{ \App\field('resources_intro_h2', __('What you will find here.', 'sage')) }}
    </h2>
    <p class="lead work-guide__intro">{{ \App\field('resources_intro_p', __('Starters are free to fork. Tool recommendations may include disclosed affiliate links — see the note at the top when they appear. The stack list at the end is what I actually use on shipped work.', 'sage')) }}</p>
  </div>
</section>

<div class="resources-body pf-section">
  <div class="container wide resources-body__grid">
    @foreach ($sections as $section)
      <section class="resources-section" aria-labelledby="resources-{{ \Illuminate\Support\Str::slug($section['title']) }}">
        <div class="resources-section__head">
          <h2 id="resources-{{ \Illuminate\Support\Str::slug($section['title']) }}" class="resources-section__title">{{ $section['title'] }}</h2>
          <p class="resources-section__intro">{{ $section['intro'] }}</p>
        </div>
        <ul class="resources-list">
          @foreach ($section['items'] as $item)
            @php
              $isAff = ! empty($item['affiliate']);
              $rel = \App\mh_outbound_rel($isAff);
            @endphp
            <li class="resources-card">
              <div class="resources-card__meta">
                @if (($item['badge'] ?? '') !== '')
                  <span class="resources-card__badge">{{ $item['badge'] }}</span>
                @endif
                @if ($isAff)
                  <span class="resources-card__aff">{{ __('Affiliate', 'sage') }}</span>
                @endif
              </div>
              <h3 class="resources-card__name">
                <a
                  href="{{ esc_url($item['url']) }}"
                  rel="{{ $rel }}"
                  @if ($isAff) data-affiliate="true" class="affiliate-link" @endif
                  @if (\App\mh_is_external_url($item['url'])) target="_blank" @endif
                >{{ $item['name'] }}@if (\App\mh_is_external_url($item['url']))<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>@endif</a>
              </h3>
              <p class="resources-card__blurb">{{ $item['blurb'] }}</p>
            </li>
          @endforeach
        </ul>
      </section>
    @endforeach
  </div>
</div>

{{-- STACK I USE (was /uses/) --}}
<section class="pf-section pf-section--alt uses-stack" id="uses" aria-labelledby="uses-intro-heading">
  <div class="container wide">
    <header class="work-guide uses-head">
      <p class="eyebrow">{{ __('Uses', 'sage') }}</p>
      <h2 id="uses-intro-heading" class="display-title is-section">
        {{ \App\field('uses_intro_h2', __('The stack I use.', 'sage')) }}
      </h2>
      <p class="lead work-guide__intro">{{ \App\field('uses_intro_p', __('What I actually use on shipped WordPress and web work — not exhaustive, just what I reach for. External links open in a new tab; affiliate links are labeled.', 'sage')) }}</p>
    </header>

    <div class="uses-body__grid">
      @foreach ($stack as $group)
        <section class="uses-section" id="uses-{{ \Illuminate\Support\Str::slug($group['title']) }}" aria-labelledby="uses-{{ \Illuminate\Support\Str::slug($group['title']) }}-heading">
          <div class="uses-section__head">
            <div class="uses-section__icon">{!! \App\mh_svg_icon($group['icon'], 20) !!}</div>
            <h3 id="uses-{{ \Illuminate\Support\Str::slug($group['title']) }}-heading" class="uses-section__title">{{ $group['title'] }}</h3>
          </div>
          <ul class="uses-list">
            @foreach ($group['items'] as $row)
              @php
                [$name, $desc, $url] = array_pad(array_values($row), 3, null);
                $affiliate = ! empty($row[3]);
              @endphp
              <li class="uses-item">
                <div class="uses-item__name">
                  @if ($url)
                    @php $external = \App\mh_is_external_url($url); @endphp
                    <a
                      href="{{ esc_url($url) }}"
                      rel="{{ \App\mh_outbound_rel($affiliate) }}"
                      @if ($external) target="_blank" @endif
                      @if ($affiliate) data-affiliate="true" class="affiliate-link" @endif
                    >{{ $name }}@if ($external)<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>@endif</a>
                    @if ($affiliate)
                      <span class="uses-item__aff">{{ __('Affiliate', 'sage') }}</span>
                    @endif
                  @else
                    {{ $name }}
                  @endif
                </div>
                <p class="uses-item__desc">{{ $desc }}</p>
              </li>
            @endforeach
          </ul>
        </section>
      @endforeach
    </div>
  </div>
</section>

@php $gh = \App\Github::fetchUser(\App\mh_github_login()); @endphp
<section class="cta-band" aria-labelledby="resources-cta-heading" data-reveal>
  <div class="container wide cta-band-inner">
    <div class="cta-band__copy">
      @if (\App\mh_is_hireable($gh))
        <p class="eyebrow eyebrow--on-dark">
          @include('partials.avail-mark', ['gh' => $gh])
          {{ __('Available now', 'sage') }}
        </p>
      @endif
      <h2 id="resources-cta-heading" class="display-title is-section">{{ __('Want this stack on your project?', 'sage') }}</h2>
      <p>{{ __('Resources here are starting points. Hire me for a production WordPress site, plugin, or web app — full-time, contract, or project.', 'sage') }}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!} {{ __('Say hello', 'sage') }}
      </a>
      <p class="cta-band__note">{{ \App\mh_reply_sla('note') }}</p>
    </div>
  </div>
</section>

@endsection
