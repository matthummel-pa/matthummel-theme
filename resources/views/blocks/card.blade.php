{{-- matthummel/card: uses the About service-card classes so it takes the theme card recipe as is.
  A link that ends the last paragraph ("... See theme development.") is shown as a button under the copy, like the theme cards.
  Cards that hold a list of links keep the list. --}}
@php
  $icon = sanitize_key((string) ($attributes['icon'] ?? ''));
  $number = trim((string) ($attributes['number'] ?? ''));
  $body = (string) $content;
  $cta = null;
  $hasList = (bool) preg_match('/<(ul|ol)\b/i', $body);
  if (! $hasList && preg_match('/(<p\b[^>]*>|<br\s*\/?>\s*|[.!?]\s+)(<a\b[^>]*\bhref="([^"]+)"[^>]*>((?:(?!<a\b|<\/a>).)*)<\/a>)\s*[.!]?\s*(<\/p>)\s*$/is', $body, $m, PREG_OFFSET_CAPTURE)) {
    // Only a link that starts a sentence or a paragraph becomes the button, and only one link wide: a sentence with links in the
    // middle of it stays as text. (3.46.1: the old pattern could swallow everything from the first link to the last.)
    $label = trim(html_entity_decode(wp_strip_all_tags($m[4][0]), ENT_QUOTES));
    $label = rtrim($label, ". \t\n");
    if ($label !== '') {
      $cta = ['url' => html_entity_decode($m[3][0], ENT_QUOTES), 'label' => $label, 'blank' => (bool) preg_match('/target="_blank"/i', $m[2][0])];
      $body = substr($body, 0, $m[2][1]).$m[5][0];
      // A paragraph that held only the link is now empty.
      $body = preg_replace('/<p[^>]*>\s*<\/p>\s*$/i', '', $body);
    }
  }
@endphp
<article class="{{ trim('about-svc-card mh-card '.($hasList ? 'about-svc-card--list ' : '').($cta ? 'about-svc-card--cta ' : '').($attributes['className'] ?? '')) }}">
  @if ($number !== '')
    <span class="about-svc-card__n" aria-hidden="true">{{ $number }}</span>
  @endif
  @if ($icon !== '')
    <div class="about-svc-card__icon" aria-hidden="true">{!! \App\mh_svg_icon($icon, 28) !!}</div>
  @endif
  <div class="about-svc-card__copy">{!! wp_kses_post($body) !!}</div>
  @if ($cta)
    <p class="about-svc-card__cta">
      <a class="shop-link shop-link--forward" href="{{ esc_url($cta['url']) }}"@if ($cta['blank']) rel="noopener" target="_blank"@endif>{{ $cta['label'] }} <span class="shop-link__icon" aria-hidden="true">&rarr;</span></a>
    </p>
  @endif
</article>
