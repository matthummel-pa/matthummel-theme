@php
  $ctaTitle = $title ?? __('Need a WordPress developer in Gettysburg?', 'sage');
  $ctaText = $text ?? __('Got a question about a post, a project, or a role? Send it over. I usually reply within a day.', 'sage');
  $ctaLabel = $label ?? __('Say hello', 'sage');
  $ctaHref = $href ?? home_url('/contact/');
  $ctaKicker = $kicker ?? __('Get in touch', 'sage');
  $ctaSecondary = $secondary ?? __('Hire me', 'sage');
  $ctaSecondaryHref = $secondaryHref ?? home_url('/hire/');
  $ctaNote = $note ?? __('Gettysburg · remote · usually within a day', 'sage');
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
      @if ($ctaSecondary !== '')
        <a class="btn btn-ghost" href="{{ esc_url($ctaSecondaryHref) }}">{{ $ctaSecondary }}</a>
      @endif
      @if ($ctaNote !== '')
        <p class="cta-band__note">{{ $ctaNote }}</p>
      @endif
    </div>
  </div>
</section>
