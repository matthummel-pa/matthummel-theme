@php
  $whoHeading = $heading ?? \App\field('who_h2', __('Who this site is for', 'sage'));
  $whoIntro = $intro ?? \App\field('who_intro', __('Developers, people learning the web, shops, and agencies can all use this site. Pick the door that fits.', 'sage'));
  $whoItems = $items ?? \App\field_rows('who_items', \App\mh_who_items());
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
        <article class="who-card">
          <h3>{{ $who['title'] ?? '' }}</h3>
          <p>{{ $who['text'] ?? '' }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>
