{{-- matthummel/section: one page section. Inner blocks are already rendered in $content. --}}
@php
  $anchor = sanitize_html_class((string) ($attributes['anchor'] ?? ''));
  $eyebrow = trim((string) ($attributes['eyebrow'] ?? ''));
  // Home sections that get a decorative line-art drawing behind their content (partials/section-illustration).
  // About sections get their own animated drawings (3.7.33); gated to the About page
  // because Home also has a #work section.
  $hasIllustration = in_array($anchor, ['inside', 'journal-posts', 'newsletter', 'uses', 'write', 'now', 'questions'], true)
    || (in_array($anchor, ['story', 'build', 'work', 'resume', 'code'], true) && is_page('about'));
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
  // About Story (3.7.34): the profile photo sits in the right column under the
  // drawing. It follows the hero block's "Show profile photo" toggle and caption.
  $portrait = null;
  if ($anchor === 'story' && is_page('about') && \App\mh_profile_photo_url(640) !== '') {
    foreach (parse_blocks((string) get_post_field('post_content', get_queried_object_id())) as $block) {
      if (($block['blockName'] ?? '') !== 'matthummel/hero') continue;
      if (! empty($block['attrs']['showPortrait'])) $portrait = trim((string) ($block['attrs']['portraitCaption'] ?? ''));
      break;
    }
  }
@endphp
<section class="{{ $classes }}" @if ($anchor !== '') id="{{ $anchor }}" @endif>
  @if ($portrait !== null)
    <div class="about-story-aside">
      {!! $illustration !!}
      @include('partials.about-portrait', ['caption' => $portrait, 'class' => 'about-portrait--story'])
    </div>
  @else
    {!! $illustration !!}
  @endif
  <div class="container wide">
    @if ($eyebrow !== '')
      <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    {!! $content !!}
  </div>
</section>
