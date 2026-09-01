{{--
  Template Name: Resources
  Free starters, paid themes, and disclosed tool recommendations (affiliate-ready).
--}}
@extends('layouts.app')

@php
  $sections = \App\mh_resources_catalog();
  $disclosureUrl = \App\mh_affiliate_disclosure_url();
  $hasAffiliate = false;
  foreach ($sections as $section) {
    foreach ($section['items'] as $item) {
      if (! empty($item['affiliate'])) {
        $hasAffiliate = true;
        break 2;
      }
    }
  }
@endphp

@section('content')

@component('partials.page-hero', ['extra' => 'page-header--resources'])
  <p class="eyebrow">{{ __('Resources', 'sage') }}</p>
  <h1 class="display-title is-hero">{{ __('Free starters, themes, and tools.', 'sage') }}</h1>
  <p class="lead">{{ __('A quiet catalog for developers and shops: open code to study, themes you can buy, and tools I use on real projects. Hire me when you want a full build.', 'sage') }}</p>
  <p class="about-hero-links" style="margin-top:1rem">
    <a href="{{ home_url('/hire/') }}">{{ __('Hire me', 'sage') }}</a>
    <a href="{{ home_url('/projects/') }}">{{ __('Browse work', 'sage') }}</a>
    <a href="{{ home_url('/code/') }}">{{ __('Code', 'sage') }}</a>
  </p>
@endcomponent

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
                  @if (\Illuminate\Support\Str::startsWith($item['url'], 'http')) target="_blank" @endif
                >{{ $item['name'] }}</a>
              </h3>
              <p class="resources-card__blurb">{{ $item['blurb'] }}</p>
            </li>
          @endforeach
        </ul>
      </section>
    @endforeach
  </div>
</div>

@php $gh = \App\Github::fetchUser(\App\mh_github_login()); @endphp
<section class="cta-band" aria-labelledby="resources-cta-heading" data-reveal>
  <div class="container wide cta-band-inner">
    <div class="cta-band__copy">
      @if (\App\mh_is_hireable($gh))
        <p class="eyebrow eyebrow--on-dark">
          @include('partials.avail-mark', ['gh' => $gh])
          {{ \App\mh_availability_label($gh, __('Open for work', 'sage')) }}
        </p>
      @endif
      <h2 id="resources-cta-heading" class="display-title is-section">{{ __('Want the full build?', 'sage') }}</h2>
      <p>{{ __('Resources here are starting points. Hire me for a production WordPress site, plugin, or web app — full-time, contract, or project.', 'sage') }}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/hire/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!} {{ __('Hire me', 'sage') }}
      </a>
      <a class="btn btn-ghost" href="{{ home_url('/projects/') }}">{{ __('See Work', 'sage') }}</a>
      <p class="cta-band__note">{{ __('Remote · usually within a day', 'sage') }}</p>
    </div>
  </div>
</section>

@endsection
