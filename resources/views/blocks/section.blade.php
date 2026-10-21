{{-- matthummel/section: one page section. Inner blocks are already rendered in $content. --}}
@php
  $anchor = sanitize_html_class((string) ($attributes['anchor'] ?? ''));
  $eyebrow = trim((string) ($attributes['eyebrow'] ?? ''));
  // Home sections that get a decorative line-art drawing behind their content (partials/section-illustration).
  $hasIllustration = in_array($anchor, ['inside', 'journal-posts', 'newsletter', 'uses', 'write', 'now', 'questions'], true);
  $classes = trim('pf-section mh-block-section '.(($attributes['tone'] ?? 'default') === 'alt' ? 'pf-section--alt ' : '').($hasIllustration ? 'mh-block-section--illus ' : '').($attributes['className'] ?? ''));
  $illustration = $hasIllustration ? view('partials.section-illustration', ['anchor' => $anchor])->render() : '';
  // FAQ (3.7.26): draw the illustration in the left column, right above the
  // "Still have a question?" card, instead of pinned to the section's bottom edge.
  // Falls back to the section-level drawing if the card is not in the content.
  if ($anchor === 'questions' && $illustration !== ''
      && preg_match('/<div\b[^>]*\bclass="[^"]*\bfaq-help-card\b/', $content, $m, PREG_OFFSET_CAPTURE)) {
    $content = substr_replace($content, $illustration, $m[0][1], 0);
    $illustration = '';
  }
@endphp
<section class="{{ $classes }}" @if ($anchor !== '') id="{{ $anchor }}" @endif>
  {!! $illustration !!}
  <div class="container wide">
    @if ($eyebrow !== '')
      <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    {!! $content !!}
  </div>
</section>
