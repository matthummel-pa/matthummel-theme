{{-- matthummel/section: one page section. Inner blocks are already rendered in $content. --}}
@php
  $anchor = sanitize_html_class((string) ($attributes['anchor'] ?? ''));
  $eyebrow = trim((string) ($attributes['eyebrow'] ?? ''));
  // Home sections that get a decorative line-art drawing behind their content (partials/section-illustration).
  $hasIllustration = in_array($anchor, ['inside', 'journal-posts', 'newsletter', 'uses', 'write', 'now', 'questions'], true);
  $classes = trim('pf-section mh-block-section '.(($attributes['tone'] ?? 'default') === 'alt' ? 'pf-section--alt ' : '').($hasIllustration ? 'mh-block-section--illus ' : '').($attributes['className'] ?? ''));
@endphp
<section class="{{ $classes }}" @if ($anchor !== '') id="{{ $anchor }}" @endif>
  @if ($hasIllustration)
    @include('partials.section-illustration', ['anchor' => $anchor])
  @endif
  <div class="container wide">
    @if ($eyebrow !== '')
      <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    {!! $content !!}
  </div>
</section>
