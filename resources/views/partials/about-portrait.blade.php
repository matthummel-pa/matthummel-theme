{{--
  Profile photo + caption. Used by the hero block, and on About by the Story
  section (3.7.34), which prints it in the right column under the drawing.
  $caption: optional line under the name. $eager: true only above the fold.
--}}
@php
  $caption = trim((string) ($caption ?? ''));
  $eager = ! empty($eager);
@endphp
<figure class="about-portrait {{ $class ?? '' }}">
  @include('partials.profile-photo', ['size' => 320, 'class' => 'about-portrait__img', 'eager' => $eager, 'alt' => __('Matt Hummel, WordPress developer', 'sage')])
  @if ($caption !== '')
    <figcaption class="about-portrait__caption">
      <span class="about-portrait__name">{{ (\App\Github::fetchUser(\App\mh_github_login())['name'] ?? '') ?: __('Matt Hummel', 'sage') }}</span>
      <span class="about-portrait__meta">{{ $caption }}</span>
    </figcaption>
  @endif
</figure>
