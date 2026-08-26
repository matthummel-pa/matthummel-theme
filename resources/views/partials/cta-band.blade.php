@php
  $ctaTitle = $title ?? __('Need a WordPress developer in Gettysburg?', 'sage');
  $ctaText = $text ?? __('A question about a post, a project, or a role is enough. I usually reply within a day.', 'sage');
  $ctaLabel = $label ?? __('Say hello', 'sage');
  $ctaHref = $href ?? home_url('/contact/');
  $ctaKicker = $kicker ?? __('Get in touch', 'sage');
  $ctaSecondary = $secondary ?? null;
  $ctaSecondaryHref = $secondaryHref ?? home_url('/hire/');
@endphp
<section class="cta-band" aria-labelledby="cta-heading" data-reveal>
  <div class="container wide cta-band-inner">
    <div class="cta-band__copy">
      @if ($ctaKicker !== '')
        <p class="eyebrow eyebrow--on-dark">{{ $ctaKicker }}</p>
      @endif
      <h2 id="cta-heading" class="display-title is-section">{{ $ctaTitle }}</h2>
      <p>{{ $ctaText }}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn-on-dark" href="{{ esc_url($ctaHref) }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ $ctaLabel }}
      </a>
      @if (! empty($ctaSecondary))
        <a class="btn btn-ghost" href="{{ esc_url($ctaSecondaryHref) }}">{{ $ctaSecondary }}</a>
      @endif
    </div>
  </div>
</section>
