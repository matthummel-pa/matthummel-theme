{{-- matthummel/hero: wraps the heading, intro and buttons in the shared page hero. --}}
@php
  $eyebrow = trim((string) ($attributes['eyebrow'] ?? ''));
  $showPortrait = ! empty($attributes['showPortrait']) && \App\mh_profile_photo_url(640) !== '';
  $caption = trim((string) ($attributes['portraitCaption'] ?? ''));
@endphp
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
