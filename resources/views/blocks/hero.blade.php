{{-- matthummel/hero: wraps the heading, intro and buttons in the shared page hero. --}}
@php
  $eyebrow = trim((string) ($attributes['eyebrow'] ?? ''));
  // On About the photo moves out of the hero into the Story section's right
  // column (blocks/section, 3.7.34); the toggle and caption still live here.
  $showPortrait = ! empty($attributes['showPortrait']) && \App\mh_profile_photo_url(640) !== '' && ! is_page('about');
  $caption = trim((string) ($attributes['portraitCaption'] ?? ''));
@endphp
@if (($attributes['layout'] ?? 'page') === 'home')
  {{-- Home: the full-height photo hero. Size, alignment and accent come from Customize → Home hero. --}}
  @php
    $hero = \App\mh_home_hero();
    // Code collage (3.8.1): tilted code cards instead of the photo, copy centred.
    $isCollage = ($attributes['background'] ?? 'photo') === 'collage';
    // Featured theme (3.21.0): a calm gradient and one theme card beside the copy.
    $isFeatured = ($attributes['background'] ?? 'photo') === 'featured';
    $heroImage = ($isCollage || $isFeatured) ? '' : \App\mh_hero_background_url();
  @endphp
  <section class="h-hero h-hero--photo h-hero--viewport{{ $hero['accent'] ? ' h-hero--accent' : '' }} h-hero--{{ $isCollage ? 'center' : $hero['align'] }} h-hero--pad-{{ $hero['pad'] }}{{ $isCollage ? ' h-hero--collage' : '' }}{{ $isFeatured ? ' h-hero--feat' : '' }} mh-block-hero">
    @if ($isCollage)
      @include('partials.hero-collage')
    @elseif ($isFeatured)
      <div class="h-hero__media h-hero__calm" aria-hidden="true"></div>
    @elseif ($heroImage !== '')
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
      @if ($isFeatured)
        @include('partials.hero-featured')
      @endif
    </div>
    @include('partials.hero-wave')
  </section>
@else
@component('partials.page-hero', ['useScene' => in_array($attributes['background'] ?? 'photo', ['scene', 'collage'], true), 'extra' => trim('mh-block-hero '.($showPortrait ? 'about-hero ' : '').($attributes['className'] ?? ''))])
  @if ($showPortrait)
    <div class="about-hero__grid">
      <div class="about-hero__copy">
        @if ($eyebrow !== '')
          <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        {!! $content !!}
      </div>
      @include('partials.about-portrait', ['caption' => $caption, 'eager' => true])
    </div>
  @else
    @if ($eyebrow !== '')
      <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    {!! $content !!}
  @endif
@endcomponent
@endif
