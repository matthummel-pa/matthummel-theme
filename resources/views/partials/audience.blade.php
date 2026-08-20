@php
  $whoHeading = $heading ?? \App\field('who_h2', __('Who this site is for', 'sage'));
  $whoIntro = $intro ?? \App\field('who_intro', __('Developers, people learning the web, shops, and agencies can all use this site. Pick the door that fits.', 'sage'));
  $whoItems = $items ?? \App\mh_who_page_items();
@endphp
<section class="pf-section{{ ! empty($alt) ? ' pf-section--alt' : '' }}" aria-labelledby="who-heading">
  <div class="container wide">
    <header class="sec-head">
      <div>
        <p class="eyebrow">{{ $kicker ?? __('Audiences', 'sage') }}</p>
        <h2 id="who-heading" class="display-title is-section">{{ $whoHeading }}</h2>
        <p class="sec-intro">{{ $whoIntro }}</p>
      </div>
    </header>
    <div class="who-grid">
      @foreach ($whoItems as $who)
        @php
          $whoTitle = (string) ($who['title'] ?? '');
          $whoText = (string) ($who['text'] ?? '');
          $whoHref = (string) ($who['href'] ?? '');
          $whoCta = (string) ($who['cta'] ?? '');
          $whoIcon = (string) ($who['icon'] ?? 'code');
        @endphp
        <article class="who-card{{ $whoHref !== '' ? ' who-card--link' : '' }}">
          @if ($whoHref !== '')
            <a class="who-card__hit" href="{{ esc_url($whoHref) }}">
              <span class="visually-hidden">{{ $whoCta !== '' ? $whoCta : $whoTitle }}</span>
            </a>
          @endif
          <div class="who-card__top">
            <span class="who-card__icon" aria-hidden="true">{!! \App\mh_svg_icon($whoIcon, 22) !!}</span>
            <span class="who-card__num" aria-hidden="true"></span>
          </div>
          <h3>{{ $whoTitle }}</h3>
          <p>{{ $whoText }}</p>
          @if ($whoHref !== '' && $whoCta !== '')
            <p class="who-card__cta" aria-hidden="true">{{ $whoCta }}</p>
          @endif
        </article>
      @endforeach
    </div>
  </div>
</section>
