{{-- matthummel/hero: wraps the heading, intro and buttons in the shared page hero. --}}
@php
  $eyebrow = trim((string) ($attributes['eyebrow'] ?? ''));
  $showPortrait = ! empty($attributes['showPortrait']) && \App\mh_profile_photo_url(640) !== '';
  $caption = trim((string) ($attributes['portraitCaption'] ?? ''));
@endphp
@if (($attributes['layout'] ?? 'page') === 'home')
  {{-- Home: the full-height photo hero. Size, alignment and accent come from Customize → Home hero. --}}
  @php
    $hero = \App\mh_home_hero();
    $heroImage = \App\mh_hero_background_url();
  @endphp
  <section class="h-hero h-hero--photo h-hero--viewport{{ $hero['accent'] ? ' h-hero--accent' : '' }} h-hero--{{ $hero['align'] }} h-hero--pad-{{ $hero['pad'] }} mh-block-hero">
    @if ($heroImage !== '')
      <div class="h-hero__media" aria-hidden="true">
        <img class="h-hero__bg" src="{{ esc_url($heroImage) }}" alt="" width="1600" height="900" decoding="async" fetchpriority="high">
        <div class="h-hero__wash"></div>
      </div>
    @endif
    <div class="container wide h-hero__inner">
      <div class="h-hero__panel">
        <div class="h-hero__copy">
          @if ($eyebrow !== '')
            <p class="h-hero__kicker">{{ $eyebrow }}</p>
          @endif
          {!! $content !!}
        </div>
      </div>
    </div>
    @include('partials.hero-wave')
  </section>
@else
@component('partials.page-hero', ['useScene' => ($attributes['background'] ?? 'photo') === 'scene', 'extra' => trim('mh-block-hero '.($showPortrait ? 'about-hero ' : '').($attributes['className'] ?? ''))])
  @if ($showPortrait)
    <div class="about-hero__grid">
      <div class="about-hero__copy">
        @if ($eyebrow !== '')
          <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        {!! $content !!}
      </div>
      <figure class="about-portrait">
        @include('partials.profile-photo', ['size' => 320, 'class' => 'about-portrait__img', 'eager' => true, 'alt' => __('Matt Hummel, WordPress developer', 'sage')])
        @if ($caption !== '')
          <figcaption class="about-portrait__caption">
            <span class="about-portrait__meta">{{ $caption }}</span>
          </figcaption>
        @endif
      </figure>
    </div>
  @else
    @if ($eyebrow !== '')
      <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    {!! $content !!}
  @endif
@endcomponent
@endif
