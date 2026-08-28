{{-- Single Project → on-site concept page (/concept/{slug}/) --}}
@php
  $postId = (int) get_the_ID();
  $card = \App\mh_project_post_to_card(get_post($postId));
  $story = \App\mh_project_concept_narrative($postId);
  $title = (string) ($card['title'] ?? get_the_title());
  $shot = (string) ($card['image'] ?? '');
  $cat = (string) ($card['cat'] ?? '');
  $place = (string) ($card['place'] ?? '');
  $tech = $card['tech'] ?? [];
  $demo = (string) ($story['demo'] !== '' ? $story['demo'] : ($card['demo'] ?? ''));
  $useUrl = \App\mh_work_contact_url($card);
  $projectsUrl = home_url('/projects/');
  $related = \App\mh_related_concept_cards($card, 3);
  $summary = (string) ($story['summary'] !== '' ? $story['summary'] : ($card['blurb'] ?? ''));
  $eyebrow = (string) ($story['eyebrow'] !== '' ? $story['eyebrow'] : __('Concept site', 'sage'));
@endphp

<article @php(post_class('concept-page'))>
  @component('partials.page-hero')
    <p class="eyebrow">
      <a class="concept-crumb" href="{{ esc_url($projectsUrl) }}">{{ __('Work', 'sage') }}</a>
      <span aria-hidden="true"> / </span>
      {{ $eyebrow }}
    </p>
    <h1 class="display-title is-hero">{{ $title }}</h1>
    <p class="lead">{{ $summary }}</p>
    <p class="pf-meta" style="margin-top:.85rem">
      @if ($cat !== '')
        <span>{{ $cat }}</span>
      @endif
      @if ($cat !== '' && $place !== '')
        <span aria-hidden="true"> · </span>
      @endif
      @if ($place !== '')
        <span>{!! \App\mh_svg_icon('map', 14) !!} {{ $place }}</span>
      @endif
    </p>
    <div class="concept-hero-actions">
      <a class="btn" href="{{ esc_url($useUrl) }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ __('Use this concept', 'sage') }}
      </a>
      @if ($demo !== '')
        <a class="btn btn-outline" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
          {!! \App\mh_svg_icon('globe', 15) !!}
          {{ __('Live demo', 'sage') }} <span aria-hidden="true">↗</span>
        </a>
      @endif
      <a class="h-text-arrow" href="{{ esc_url($projectsUrl) }}">{{ __('All concepts', 'sage') }} →</a>
    </div>
  @endcomponent

  <div class="container wide page-block concept-layout">
    @if ($shot !== '')
      <figure class="concept-shot">
        <img
          src="{{ esc_url($shot) }}"
          alt="{{ esc_attr(sprintf(__('Screenshot of the %s concept', 'sage'), $title)) }}"
          width="1200"
          height="675"
          loading="eager"
          decoding="async"
        >
        <figcaption>{{ __('Example layout — starting point for a real Gettysburg shop build.', 'sage') }}</figcaption>
      </figure>
    @endif

    @if ($story['metrics'] !== [])
      <div class="concept-metrics" role="list">
        @foreach ($story['metrics'] as $metric)
          <div class="concept-metric" role="listitem">
            <strong>{{ $metric[0] }}</strong>
            <span>{{ $metric[1] }}</span>
          </div>
        @endforeach
      </div>
    @endif

    <div class="concept-grid">
      <div class="concept-main">
        @if ($story['challenge'] !== '')
          <section class="concept-section" aria-labelledby="concept-challenge">
            <h2 id="concept-challenge">{{ __('The problem', 'sage') }}</h2>
            <p>{{ $story['challenge'] }}</p>
          </section>
        @endif

        @if ($story['approach'] !== '')
          <section class="concept-section" aria-labelledby="concept-approach">
            <h2 id="concept-approach">{{ __('How I shaped it', 'sage') }}</h2>
            <p>{{ $story['approach'] }}</p>
          </section>
        @endif

        @if ($story['result'] !== '')
          <section class="concept-section" aria-labelledby="concept-result">
            <h2 id="concept-result">{{ __('What you get', 'sage') }}</h2>
            <p>{{ $story['result'] }}</p>
          </section>
        @endif

        @if ($story['deliverables'] !== [])
          <section class="concept-section" aria-labelledby="concept-deliverables">
            <h2 id="concept-deliverables">{{ __('Included in this concept', 'sage') }}</h2>
            <ul class="concept-list">
              @foreach ($story['deliverables'] as $item)
                <li>{{ $item }}</li>
              @endforeach
            </ul>
          </section>
        @endif
      </div>

      <aside class="concept-aside" aria-label="{{ __('Concept details', 'sage') }}">
        @if (! empty($tech))
          <div class="concept-aside-card">
            <h2 class="concept-aside-title">{{ __('Stack', 'sage') }}</h2>
            <p class="pill-row">
              @foreach ($tech as $t)
                <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ $t }}</span>
              @endforeach
            </p>
          </div>
        @endif

        <div class="concept-aside-card concept-aside-card--cta">
          <h2 class="concept-aside-title">{{ __('Want this for your shop?', 'sage') }}</h2>
          <p>{{ __('Tell me which parts fit and what you’d change. I usually reply within a day.', 'sage') }}</p>
          <a class="btn" href="{{ esc_url($useUrl) }}">{!! \App\mh_svg_icon('mail', 16) !!} {{ __('Say hello', 'sage') }}</a>
          @if ($demo !== '')
            <a class="btn btn-outline" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">{{ __('Open live demo', 'sage') }} ↗</a>
          @endif
        </div>

        <p class="concept-aside-note">
          {{ __('This is a concept example on matthummel.com — not a live client site. Hire me to adapt it for your Gettysburg business.', 'sage') }}
        </p>
      </aside>
    </div>

    @if ($related !== [])
      <section class="concept-related" aria-labelledby="concept-related-heading">
        <div class="concept-related__head">
          <h2 id="concept-related-heading">{{ __('More concepts', 'sage') }}</h2>
          <a class="h-text-arrow" href="{{ esc_url($projectsUrl) }}">{{ __('Browse all', 'sage') }} →</a>
        </div>
        <div class="concept-related__grid">
          @foreach ($related as $p)
            <article class="concept-related-card">
              @if (! empty($p['image']))
                <a class="concept-related-card__img" href="{{ esc_url($p['url'] ?? \App\mh_concept_page_url((string) ($p['slug'] ?? ''))) }}" aria-hidden="true" tabindex="-1">
                  <img src="{{ esc_url($p['image']) }}" alt="" width="640" height="360" loading="lazy" decoding="async">
                </a>
              @endif
              <div class="concept-related-card__body">
                <p class="pf-meta">{{ $p['cat'] ?? '' }} · {{ $p['place'] ?? '' }}</p>
                <h3><a href="{{ esc_url($p['url'] ?? \App\mh_concept_page_url((string) ($p['slug'] ?? ''))) }}">{{ $p['title'] ?? '' }}</a></h3>
                <p>{{ $p['blurb'] ?? '' }}</p>
              </div>
            </article>
          @endforeach
        </div>
      </section>
    @endif
  </div>

  @include('partials.cta-band', [
    'kicker' => __('Work with me', 'sage'),
    'title' => sprintf(__('Like %s?', 'sage'), $title),
    'text' => __('Say which concept fits your shop and what you’d change. I usually reply within a day.', 'sage'),
    'label' => __('Say hello', 'sage'),
    'href' => $useUrl,
    'secondary' => __('See services', 'sage'),
    'secondaryHref' => home_url('/services/'),
  ])
</article>
