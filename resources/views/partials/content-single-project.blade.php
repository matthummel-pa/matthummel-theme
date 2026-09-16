{{-- Single project at /projects/{slug}/ --}}
@php
  $postId = (int) get_the_ID();
  $post = get_post($postId);
  $card = $post instanceof WP_Post ? \App\mh_project_post_to_card($post) : [];
  $story = \App\mh_project_concept_narrative($postId);
  $case = \App\mh_project_case_study($postId, $card, $story);
  $docs = \App\mh_project_buyer_docs($postId, $card);
  $gh = \App\mh_project_github_facts($postId, $card);
  $slides = \App\mh_project_page_slides($postId, $card);
  $title = (string) ($card['title'] ?? get_the_title());
  $cat = (string) ($card['cat'] ?? '');
  $place = (string) ($card['place'] ?? '');
  $tech = $card['tech'] ?? [];
  $demo = (string) ($story['demo'] !== '' ? $story['demo'] : ($card['demo'] ?? ''));
  if ($demo === '' && $gh['homepage'] !== '') {
    $demo = (string) $gh['homepage'];
  }
  $github = (string) ($gh['url'] !== '' ? $gh['url'] : ($card['github'] ?? $card['concept'] ?? ''));
  $helloUrl = function_exists('\\App\\mh_work_help_url') ? \App\mh_work_help_url($card) : \App\mh_work_contact_url($card);
  $projectsUrl = home_url('/projects/');
  $related = \App\mh_related_concept_cards($card, 3);
  $summary = (string) ($story['summary'] !== '' ? $story['summary'] : ($card['blurb'] ?? ''));
  if ($summary === '' && $gh['desc'] !== '') {
    $summary = (string) $gh['desc'];
  }
  $eyebrow = \App\mh_project_display_eyebrow((string) ($story['eyebrow'] ?? ''));
  $deliverables = is_array($story['deliverables'] ?? null) ? $story['deliverables'] : [];
  $benefits = is_array($story['benefits'] ?? null) ? $story['benefits'] : [];
  $features = $deliverables !== [] ? $deliverables : $benefits;
  if ($deliverables !== [] && $benefits !== []) {
    $features = array_values(array_unique(array_merge($deliverables, $benefits)));
  }
  $architecture = (string) ($case['architecture'] !== '' ? $case['architecture'] : ($docs['architecture'] ?? ''));
  $handoff = (string) ($case['handoff'] !== '' ? $case['handoff'] : ($docs['handoff'] ?? ''));
  $audience = (string) ($docs['audience'] ?? '');
  $specs = is_array($docs['specs'] ?? null) ? $docs['specs'] : [];
  $faq = is_array($story['faq'] ?? null) ? $story['faq'] : [];
  if ($faq === [] && is_array($docs['faq'] ?? null)) {
    $faq = $docs['faq'];
  }
  $metrics = is_array($story['metrics'] ?? null) ? $story['metrics'] : [];
  $heroImage = (string) ($slides[0]['src'] ?? '');
  $heroImageAlt = (string) ($slides[0]['alt'] ?? sprintf(__('Screenshot of %s', 'sage'), $title));
  $ghStats = array_values(array_filter([
    ['value' => number_format_i18n((int) $gh['stars']), 'label' => __('Stars', 'sage'), 'show' => $gh['has_repo']],
    ['value' => number_format_i18n((int) $gh['forks']), 'label' => __('Forks', 'sage'), 'show' => $gh['has_repo']],
    ['value' => number_format_i18n((int) $gh['watchers']), 'label' => __('Watchers', 'sage'), 'show' => $gh['has_repo'] && (int) $gh['watchers'] > 0],
    ['value' => $gh['lang'], 'label' => __('Language', 'sage'), 'show' => $gh['lang'] !== ''],
    ['value' => $gh['license'], 'label' => __('License', 'sage'), 'show' => $gh['license'] !== ''],
    ['value' => $gh['release'] !== '' ? $gh['release'] : $gh['version'], 'label' => __('Release', 'sage'), 'show' => $gh['release'] !== '' || $gh['version'] !== ''],
    ['value' => $gh['pushed_label'], 'label' => __('Updated', 'sage'), 'show' => $gh['pushed_label'] !== ''],
    ['value' => number_format_i18n((int) $gh['issues']), 'label' => __('Open issues', 'sage'), 'show' => $gh['has_repo']],
  ], static fn ($row) => ! empty($row['show']) && $row['value'] !== ''));
@endphp

<article @php(post_class('concept-page project-page'))>
  @component('partials.page-hero')
    <p class="eyebrow">
      <a class="concept-crumb" href="{{ esc_url($projectsUrl) }}">{{ __('Projects', 'sage') }}</a>
      <span aria-hidden="true"> / </span>
      {{ $eyebrow }}
    </p>
    <h1 class="display-title is-hero">{{ $title }}</h1>
    @if (! empty($case['notice']))
      <p class="concept-spec-banner" role="note">{{ $case['notice'] }}</p>
    @endif
    @if ($summary !== '')
      <p class="lead">{{ $summary }}</p>
    @endif
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
      @if ($gh['repo'] !== '')
        <span aria-hidden="true"> · </span>
        <span>{{ $gh['owner'] }}/{{ $gh['repo'] }}</span>
      @endif
    </p>
    <div class="concept-hero-actions">
      @if ($demo !== '')
        <a class="btn" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
          {!! \App\mh_svg_icon('globe', 15) !!}
          {{ __('Live demo', 'sage') }} <span aria-hidden="true">↗</span>
        </a>
      @endif
      <a class="{{ $demo !== '' ? 'btn btn-outline' : 'btn' }}" href="{{ esc_url($helloUrl) }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ __('Say hello', 'sage') }}
      </a>
      @if ($github !== '' && str_starts_with($github, 'http'))
        <a class="h-text-arrow" href="{{ esc_url($github) }}" rel="noopener" target="_blank">
          {{ __('View code', 'sage') }} <span aria-hidden="true">↗</span>
        </a>
      @endif
      <a class="h-text-arrow" href="{{ esc_url($projectsUrl) }}">
        {{ __('All projects', 'sage') }} →
      </a>
    </div>
  @endcomponent

  <div class="container wide page-block project-stage">
    <div class="project-stage__gallery pf-product-gallery" data-product-gallery>
      @if ($slides !== [])
        <figure
          class="pf-product-gallery__stage project-stage__shot"
          @if (count($slides) > 1)
            role="tabpanel"
            id="pf-gallery-panel"
            aria-labelledby="pf-gallery-tab-0"
          @endif
        >
          <img
            src="{{ esc_url($heroImage) }}"
            alt="{{ esc_attr($heroImageAlt) }}"
            width="960"
            height="600"
            loading="eager"
            decoding="async"
            data-gallery-main
          >
        </figure>
        @if (count($slides) > 1)
          <div class="pf-product-gallery__thumbs" role="tablist" aria-label="{{ __('Project screenshots', 'sage') }}">
            @foreach ($slides as $i => $slide)
              <button
                type="button"
                class="pf-product-gallery__thumb{{ $i === 0 ? ' is-active' : '' }}"
                role="tab"
                id="pf-gallery-tab-{{ $i }}"
                aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                aria-controls="pf-gallery-panel"
                tabindex="{{ $i === 0 ? '0' : '-1' }}"
                aria-label="{{ esc_attr($slide['alt'] !== '' ? $slide['alt'] : sprintf(__('Screenshot %d', 'sage'), $i + 1)) }}"
                data-gallery-index="{{ $i }}"
                data-gallery-src="{{ esc_url($slide['src']) }}"
                data-gallery-alt="{{ esc_attr($slide['alt']) }}"
              >
                <img
                  src="{{ esc_url($slide['src']) }}"
                  alt=""
                  width="160"
                  height="100"
                  loading="{{ $i < 4 ? 'eager' : 'lazy' }}"
                  decoding="async"
                >
              </button>
            @endforeach
          </div>
        @endif
        <p class="project-stage__caption">{{ __('Sample project. Not a client site.', 'sage') }}</p>
      @endif
    </div>

    <aside class="project-stage__info" aria-label="{{ __('Project details', 'sage') }}">
      @if ($gh['desc'] !== '' && $gh['desc'] !== $summary)
        <p class="project-stage__gh-desc">{{ $gh['desc'] }}</p>
      @endif

      @if ($ghStats !== [])
        <dl class="project-stat-grid">
          @foreach ($ghStats as $stat)
            <div class="project-stat">
              <dt>{{ $stat['label'] }}</dt>
              <dd>{{ $stat['value'] }}</dd>
            </div>
          @endforeach
        </dl>
      @endif

      @if ($specs !== [])
        <ul class="project-spec-list">
          @foreach ($specs as $spec)
            <li>
              <span>{{ $spec[0] }}</span>
              @if (is_string($spec[1] ?? null) && preg_match('#^https?://#', (string) $spec[1]) === 1)
                <a href="{{ esc_url($spec[1]) }}" rel="noopener" target="_blank">{{ $spec[1] }}</a>
              @else
                <strong>{{ $spec[1] ?? '' }}</strong>
              @endif
            </li>
          @endforeach
        </ul>
      @endif

      @if (! empty($tech))
        <p class="pill-row">
          @foreach ($tech as $t)
            <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ $t }}</span>
          @endforeach
        </p>
      @endif

      @if ($gh['topics'] !== [])
        <p class="pill-row">
          @foreach ($gh['topics'] as $topic)
            <span class="pill">{{ $topic }}</span>
          @endforeach
        </p>
      @endif

      @if ($gh['languages'] !== [])
        <p class="project-langs">
          <span>{{ __('Languages', 'sage') }}</span>
          {{ implode(' · ', array_slice($gh['languages'], 0, 6)) }}
        </p>
      @endif

      @if ($gh['compatible'] !== '')
        <p class="project-langs">
          <span>{{ __('Compatible', 'sage') }}</span>
          {{ $gh['compatible'] }}
        </p>
      @endif

      <div class="project-stage__links">
        @if ($github !== '' && str_starts_with($github, 'http'))
          <a class="btn btn-outline" href="{{ esc_url($github) }}" rel="noopener" target="_blank">
            {!! \App\mh_svg_icon('github', 15) !!}
            {{ __('Repository', 'sage') }}
          </a>
        @endif
        @if ($gh['release_url'] !== '')
          <a class="h-text-arrow" href="{{ esc_url($gh['release_url']) }}" rel="noopener" target="_blank">
            {{ __('Latest release', 'sage') }} ↗
          </a>
        @endif
      </div>
    </aside>
  </div>

  @if ($features !== [])
    <section class="container wide page-block project-features" aria-labelledby="project-features">
      <h2 id="project-features" class="display-title is-section">{{ __('What is in this sample', 'sage') }}</h2>
      <ul class="project-feat-grid">
        @foreach ($features as $item)
          <li class="project-feat">{{ $item }}</li>
        @endforeach
      </ul>
    </section>
  @endif

  @if ($metrics !== [])
    <section class="container wide page-block" aria-labelledby="project-metrics">
      <h2 id="project-metrics" class="display-title is-section">{{ __('At a glance', 'sage') }}</h2>
      <div class="concept-metrics">
        @foreach ($metrics as $metric)
          <div class="concept-metric">
            <strong>{{ $metric[0] ?? '' }}</strong>
            <span>{{ $metric[1] ?? '' }}</span>
          </div>
        @endforeach
      </div>
    </section>
  @endif

  <div class="container wide page-block concept-layout">
    <div class="concept-story">
      @if ($audience !== '')
        <section class="concept-story__block">
          <h2>{{ __('Who it is for', 'sage') }}</h2>
          <p>{{ $audience }}</p>
        </section>
      @endif
      @if (($story['challenge'] ?? '') !== '')
        <section class="concept-story__block">
          <h2>{{ __('Why I built it', 'sage') }}</h2>
          <p>{{ $story['challenge'] }}</p>
        </section>
      @endif
      @if (($story['approach'] ?? '') !== '')
        <section class="concept-story__block">
          <h2>{{ __('What I did', 'sage') }}</h2>
          <p>{{ $story['approach'] }}</p>
        </section>
      @endif
      @if (($story['result'] ?? '') !== '')
        <section class="concept-story__block">
          <h2>{{ __('What you can use', 'sage') }}</h2>
          <p>{{ $story['result'] }}</p>
        </section>
      @endif
    </div>

    @if ($architecture !== '')
      <section class="project-detail-block" aria-labelledby="project-architecture">
        <h2 id="project-architecture">{{ __('Architecture', 'sage') }}</h2>
        @foreach (\App\mh_project_prose_paragraphs($architecture) as $para)
          <p>{{ $para }}</p>
        @endforeach
      </section>
    @endif

    @if ($handoff !== '')
      <section class="project-detail-block" aria-labelledby="project-handoff">
        <h2 id="project-handoff">{{ __('Handoff', 'sage') }}</h2>
        @foreach (\App\mh_project_prose_paragraphs($handoff) as $para)
          <p>{{ $para }}</p>
        @endforeach
      </section>
    @endif

    @if ($faq !== [])
      <section class="project-detail-block" aria-labelledby="project-faq">
        <h2 id="project-faq">{{ __('Questions', 'sage') }}</h2>
        <div class="faq-list">
          @foreach ($faq as $item)
            @php
              $q = (string) ($item['q'] ?? $item[0] ?? '');
              $a = (string) ($item['a'] ?? $item[1] ?? '');
            @endphp
            @if ($q !== '' && $a !== '')
              <div class="project-faq">
                <h3>{{ $q }}</h3>
                <p>{{ $a }}</p>
              </div>
            @endif
          @endforeach
        </div>
      </section>
    @endif
  </div>

  @if ($related !== [])
    <section class="pf-section pf-section--alt" aria-labelledby="related-projects">
      <div class="container wide">
        <h2 id="related-projects" class="display-title is-section">{{ __('More projects', 'sage') }}</h2>
        <div class="work-grid">
          @foreach ($related as $p)
            @include('partials.work-card', ['p' => $p])
          @endforeach
        </div>
      </div>
    </section>
  @endif

  @include('partials.cta-band', [
    'kicker' => __('Work with me', 'sage'),
    'title' => sprintf(__('Like %s?', 'sage'), $title),
    'text' => __('Tell me what you would change, or write about a role. I usually reply in a day.', 'sage'),
    'label' => __('Say hello', 'sage'),
    'secondary' => __('Hire me', 'sage'),
    'secondaryHref' => home_url('/hire/'),
  ])
</article>
