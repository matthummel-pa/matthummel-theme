{{-- Single Project → on-site project page (/projects/{slug}/) --}}
@php
  $postId = (int) get_the_ID();
  $card = \App\mh_project_post_to_card(get_post($postId));
  $story = \App\mh_project_concept_narrative($postId);
  $docs = \App\mh_project_buyer_docs($postId, $card);
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
  $eyebrow = \App\mh_project_display_eyebrow((string) ($story['eyebrow'] ?? ''));
@endphp

<article @php(post_class('concept-page'))>
  @component('partials.page-hero', ['extra' => 'page-header--project', 'innerClass' => 'page-header-inner--split'])
    <div class="concept-hero-copy">
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
          {{ __('Use this project', 'sage') }}
        </a>
        @if ($demo !== '')
          <a class="btn btn-outline" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
            {!! \App\mh_svg_icon('globe', 15) !!}
            {{ __('Live demo', 'sage') }} <span aria-hidden="true">↗</span>
          </a>
        @endif
        <a class="h-text-arrow" href="{{ esc_url($projectsUrl) }}">{{ __('All projects', 'sage') }} →</a>
      </div>
    </div>
    @if ($shot !== '')
      <figure class="concept-hero-shot">
        <div class="concept-hero-shot__bar" aria-hidden="true">
          <span></span><span></span><span></span>
        </div>
        <img
          src="{{ esc_url($shot) }}"
          alt="{{ esc_attr(sprintf(__('Screenshot of the %s project', 'sage'), $title)) }}"
          width="1200"
          height="675"
          loading="eager"
          decoding="async"
        >
        <figcaption>{{ __('Example layout — starting point for a real WordPress build.', 'sage') }}</figcaption>
      </figure>
    @endif
  @endcomponent

  <div class="container wide page-block concept-layout">
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
            @foreach (\App\mh_project_prose_paragraphs($story['challenge']) as $para)
              <p>{{ $para }}</p>
            @endforeach
          </section>
        @endif

        @if ($story['approach'] !== '')
          <section class="concept-section" aria-labelledby="concept-approach">
            <h2 id="concept-approach">{{ __('How I shaped it', 'sage') }}</h2>
            @foreach (\App\mh_project_prose_paragraphs($story['approach']) as $para)
              <p>{{ $para }}</p>
            @endforeach
          </section>
        @endif

        @if ($story['result'] !== '')
          <section class="concept-section" aria-labelledby="concept-result">
            <h2 id="concept-result">{{ __('What you get', 'sage') }}</h2>
            @foreach (\App\mh_project_prose_paragraphs($story['result']) as $para)
              <p>{{ $para }}</p>
            @endforeach
          </section>
        @endif

        @if ($docs['architecture'] !== '')
          <section class="concept-section" aria-labelledby="concept-architecture">
            <h2 id="concept-architecture">{{ __('How it’s built', 'sage') }}</h2>
            @foreach (\App\mh_project_prose_paragraphs($docs['architecture']) as $para)
              <p>{{ $para }}</p>
            @endforeach
          </section>
        @endif

        @if ($story['deliverables'] !== [])
          <section class="concept-section" aria-labelledby="concept-deliverables">
            <h2 id="concept-deliverables">{{ __('Included in this project', 'sage') }}</h2>
            <ul class="concept-list">
              @foreach ($story['deliverables'] as $item)
                <li>{{ $item }}</li>
              @endforeach
            </ul>
          </section>
        @endif

        @if ($docs['audience'] !== '')
          <section class="concept-section" aria-labelledby="concept-audience">
            <h2 id="concept-audience">{{ __('Who this is for', 'sage') }}</h2>
            @foreach (\App\mh_project_prose_paragraphs($docs['audience']) as $para)
              <p>{{ $para }}</p>
            @endforeach
          </section>
        @endif

        @if ($docs['handoff'] !== '')
          <section class="concept-section" aria-labelledby="concept-handoff">
            <h2 id="concept-handoff">{{ __('What ships', 'sage') }}</h2>
            @foreach (\App\mh_project_prose_paragraphs($docs['handoff']) as $para)
              <p>{{ $para }}</p>
            @endforeach
          </section>
        @endif

        @if ($docs['faq'] !== [])
          <section class="concept-section concept-faq" aria-labelledby="concept-faq">
            <h2 id="concept-faq">{{ __('For shops and agencies', 'sage') }}</h2>
            <dl class="concept-faq-list">
              @foreach ($docs['faq'] as $item)
                <div class="concept-faq-item">
                  <dt>{{ $item['q'] }}</dt>
                  <dd>{{ $item['a'] }}</dd>
                </div>
              @endforeach
            </dl>
          </section>
        @endif
      </div>

      <aside class="concept-aside" aria-label="{{ __('Project details', 'sage') }}">
        @if ($docs['specs'] !== [])
          <div class="concept-aside-card">
            <h2 class="concept-aside-title">{{ __('Spec', 'sage') }}</h2>
            <dl class="concept-specs">
              @foreach ($docs['specs'] as $spec)
                <div class="concept-specs__row">
                  <dt>{{ $spec[0] }}</dt>
                  <dd>
                    @if ($spec[0] === __('Live demo', 'sage'))
                      <a href="{{ esc_url($spec[1]) }}" rel="noopener" target="_blank">{{ __('Open demo', 'sage') }} ↗</a>
                    @else
                      {{ $spec[1] }}
                    @endif
                  </dd>
                </div>
              @endforeach
            </dl>
          </div>
        @endif

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
          {{ __('This is an example project on matthummel.com — not a live client site. Hire me to adapt it for your Gettysburg business.', 'sage') }}
        </p>
      </aside>
    </div>

    @if ($related !== [])
      <section class="concept-related" aria-labelledby="concept-related-heading">
        <div class="concept-related__head">
          <h2 id="concept-related-heading">{{ __('More projects', 'sage') }}</h2>
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
    'text' => __('Say which project fits your shop and what you’d change. I usually reply within a day.', 'sage'),
    'label' => __('Say hello', 'sage'),
    'href' => $useUrl,
    'secondary' => __('See services', 'sage'),
    'secondaryHref' => home_url('/services/'),
  ])
</article>
