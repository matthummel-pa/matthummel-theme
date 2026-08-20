@php
  $ctaTitle = $title ?? __('Say hello', 'sage');
  $ctaText = $text ?? __('A question about a post, a snippet, or a WordPress build is enough.', 'sage');
  $ctaLabel = $label ?? __('Send a note', 'sage');
  $ctaHref = $href ?? home_url('/contact/');
@endphp
<section class="cta-band" aria-labelledby="cta-heading">
  <div class="container wide cta-band-inner">
    <div>
      @if (! empty($kicker))
        <p class="eyebrow eyebrow--on-dark">{{ $kicker }}</p>
      @endif
      <h2 id="cta-heading" class="display-title is-section">{{ $ctaTitle }}</h2>
      <p>{{ $ctaText }}</p>
    </div>
    <a class="btn btn-on-dark" href="{{ esc_url($ctaHref) }}">{{ $ctaLabel }}</a>
  </div>
</section>
