{{-- matthummel/section: one page section. Inner blocks are already rendered in $content. --}}
@php
  $anchor = sanitize_html_class((string) ($attributes['anchor'] ?? ''));
  $eyebrow = trim((string) ($attributes['eyebrow'] ?? ''));
  $classes = trim('pf-section mh-block-section '.(($attributes['tone'] ?? 'default') === 'alt' ? 'pf-section--alt ' : '').($attributes['className'] ?? ''));
@endphp
<section class="{{ $classes }}" @if ($anchor !== '') id="{{ $anchor }}" @endif>
  <div class="container wide">
    @if ($eyebrow !== '')
      <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    {!! $content !!}
  </div>
</section>
