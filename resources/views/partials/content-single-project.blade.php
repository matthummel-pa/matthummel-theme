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
  $releaseLabel = (string) $gh['version'];
  if ($gh['release'] !== '' && function_exists('\\App\\mh_project_is_semverish') && \App\mh_project_is_semverish($gh['release'])) {
    $releaseLabel = (string) $gh['release'];
  } elseif ($releaseLabel === '' && $gh['release'] !== '') {
    $releaseLabel = (string) $gh['release'];
  }
  $ghStats = array_values(array_filter([
    ['value' => number_format_i18n((int) $gh['stars']), 'label' => __('Stars', 'sage'), 'show' => $gh['has_repo']],
    ['value' => number_format_i18n((int) $gh['forks']), 'label' => __('Forks', 'sage'), 'show' => $gh['has_repo']],
    ['value' => number_format_i18n((int) $gh['watchers']), 'label' => __('Watchers', 'sage'), 'show' => $gh['has_repo'] && (int) $gh['watchers'] > 0],
    ['value' => $gh['lang'], 'label' => __('Language', 'sage'), 'show' => $gh['lang'] !== ''],
    ['value' => $gh['license'], 'label' => __('License', 'sage'), 'show' => $gh['license'] !== ''],
    ['value' => $releaseLabel, 'label' => __('Release', 'sage'), 'show' => $releaseLabel !== ''],
    ['value' => $gh['pushed_label'], 'label' => __('Updated', 'sage'), 'show' => $gh['pushed_label'] !== ''],
    ['value' => number_format_i18n((int) $gh['issues']), 'label' => __('Open issues', 'sage'), 'show' => $gh['has_repo']],
  ], static fn ($row) => ! empty($row['show']) && $row['value'] !== ''));
  $palette = function_exists('\\App\\mh_project_brand_palette_pairs')
    ? \App\mh_project_brand_palette_pairs($postId)
    : [];
  $tagline = trim((string) get_post_meta($postId, '_mh_project_brand_tagline', true));
  $projectSlug = (string) ($card['slug'] ?? get_post_field('post_name', $postId));
  $permalink = (string) get_permalink($postId);
  $buyUrl = (string) ($card['buy_url'] ?? '');
  $buyLabel = (string) ($card['buy_label'] ?? '');
  $priceLabel = (string) ($card['price_label'] ?? '');
@endphp

<article {!! post_class('concept-page project-page') !!}>
  @component('partials.page-hero', ['extra' => 'page-header--project', 'image' => $heroImage, 'imageAlt' => $heroImageAlt])
    <p class="project-hero-crumb">
      <a class="concept-crumb" href="{{ esc_url($projectsUrl) }}">{{ __('Projects', 'sage') }}</a>
    </p>
    <div class="project-hero-head">
      @include('partials.project-type-row', ['p' => $card])
      <h1 class="display-title is-hero">{{ $title }}</h1>
    </div>
    @if ($tagline !== '')
      <p class="project-hero-tagline">{{ $tagline }}</p>
    @endif
    @if (! empty($case['notice']))
      <p class="concept-spec-banner" role="note">{{ $case['notice'] }}</p>
    @endif
    @if ($summary !== '')
      <p class="lead">{{ $summary }}</p>
    @endif
    @if ($gh['repo'] !== '')
      <p class="project-hero-repo">{{ $gh['owner'] }}/{{ $gh['repo'] }}</p>
    @endif
    <div class="concept-hero-actions">
      @if ($demo !== '')
        <a class="btn" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
          {!! \App\mh_svg_icon('globe', 15) !!}
          {{ __('Live demo', 'sage') }} <span aria-hidden="true">↗</span>
        </a>
      @endif
      @if ($buyUrl !== '')
        <a class="{{ $demo !== '' ? 'btn btn-outline' : 'btn' }}" href="{{ esc_url($buyUrl) }}">
          {!! \App\mh_svg_icon($priceLabel === __('Free', 'sage') ? 'download' : 'cart', 15) !!}
          {{ $buyLabel !== '' ? $buyLabel : __('Get the pack', 'sage') }}
        </a>
      @endif
      <a class="{{ $demo !== '' || $buyUrl !== '' ? 'btn btn-outline' : 'btn' }}" href="#project-ask">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ __('Ask about this', 'sage') }}
      </a>
      @if ($github !== '' && str_starts_with($github, 'http'))
        <a class="h-text-arrow" href="{{ esc_url($github) }}" rel="noopener" target="_blank">
          {{ __('View code', 'sage') }} <span aria-hidden="true">↗</span>
        </a>
      @endif
      <button type="button" class="h-text-arrow post-copy-link" data-copy="{{ esc_url($permalink) }}">
        <span>{{ __('Copy link', 'sage') }}</span>
      </button>
      <a class="h-text-arrow" href="{{ esc_url($projectsUrl) }}">
        {{ __('All projects', 'sage') }} →
      </a>
    </div>
  @endcomponent

@php
  $projectPills = [];
  if ($slides !== []) {
    $projectPills[] = ['screenshots', __('Screenshots', 'sage')];
  }
  if ($ghStats !== []) {
    $projectPills[] = ['project-build-notes', __('Build notes', 'sage')];
  }
  if ($specs !== [] || $gh['languages'] !== [] || $gh['compatible'] !== '') {
    $projectPills[] = ['project-theme-details', __('Theme details', 'sage')];
  }
  if ($palette !== []) {
    $projectPills[] = ['project-palette', __('Palette', 'sage')];
  }
  if (! empty($tech)) {
    $projectPills[] = ['project-runs-on', __('Runs on', 'sage')];
  }
  if ($gh['topics'] !== []) {
    $projectPills[] = ['project-theme-tags', __('Tags', 'sage')];
  }
  if ($features !== []) {
    $projectPills[] = ['project-features', __('What’s included', 'sage')];
  }
  if ($architecture !== '') {
    $projectPills[] = ['project-architecture', __('Architecture', 'sage')];
  }
  if ($handoff !== '') {
    $projectPills[] = ['project-handoff', __('Handoff', 'sage')];
  }
  if ($faq !== []) {
    $projectPills[] = ['project-faq', __('Questions', 'sage')];
  }
  $projectPills[] = ['project-feedback', __('Like / star', 'sage')];
  $projectPills[] = ['comments', __('Notes', 'sage')];
  $projectPills[] = ['project-ask', __('Ask a question', 'sage')];
@endphp
@include('partials.page-nav', ['pills' => $projectPills])

  <div class="container wide page-block project-stage" id="screenshots">
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
          <button
            type="button"
            class="pf-product-gallery__main"
            id="pf-gallery-main"
            data-gallery-open
            data-gallery-index="0"
            aria-label="{{ esc_attr(sprintf(__('Open screenshot: %s', 'sage'), $heroImageAlt)) }}"
          >
            <img
              src="{{ esc_url($heroImage) }}"
              alt="{{ esc_attr($heroImageAlt) }}"
              width="960"
              height="600"
              loading="eager"
              fetchpriority="high"
              decoding="async"
              class="skip-lazy"
              data-no-lazy="1"
              data-gallery-main
            >
          </button>
        </figure>
        <p class="project-stage__caption" data-gallery-caption>{{ $heroImageAlt }}</p>
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
                  @if ($i < 4)
                    class="skip-lazy"
                    data-no-lazy="1"
                  @endif
                >
              </button>
            @endforeach
          </div>
        @endif
        <p class="project-stage__note" role="note">
          <span class="project-stage__note-kicker">{{ __('Note', 'sage') }}</span>
          <span class="project-stage__note-text">{{ __('Sample project. Not a client site.', 'sage') }}</span>
        </p>
      @endif
    </div>

    <div class="project-stage__react" id="project-feedback">
      @include('partials.project-react', ['postId' => $postId, 'github' => $github])
    </div>

    <aside class="project-stage__info" aria-label="{{ __('Project details', 'sage') }}">
      @if ($gh['desc'] !== '' && $gh['desc'] !== $summary)
        <p class="project-stage__gh-desc">{{ $gh['desc'] }}</p>
      @endif

      @if ($ghStats !== [])
        <section class="project-info-section" aria-labelledby="project-build-notes">
          <h2 id="project-build-notes" class="display-title is-section">{{ __('Build notes', 'sage') }}</h2>
          <dl class="project-stat-grid">
            @foreach ($ghStats as $stat)
              <div class="project-stat">
                <dt>{{ $stat['label'] }}</dt>
                <dd>{{ $stat['value'] }}</dd>
              </div>
            @endforeach
          </dl>
        </section>
      @endif

      @if ($specs !== [] || $gh['languages'] !== [] || $gh['compatible'] !== '')
        <section class="project-info-section" aria-labelledby="project-theme-details">
          <h2 id="project-theme-details" class="display-title is-section">{{ __('Theme details', 'sage') }}</h2>
          <div class="project-spec-block">
            @if ($specs !== [])
              <ul class="project-spec-list">
                @foreach ($specs as $spec)
                  <li>
                    <span class="project-spec-label">{{ $spec[0] }}</span>
                    @if (is_string($spec[1] ?? null) && preg_match('#^https?://#', (string) $spec[1]) === 1)
                      <a class="project-spec-value" href="{{ esc_url($spec[1]) }}" rel="noopener" target="_blank">{{ $spec[1] }}</a>
                    @else
                      <span class="project-spec-value">{{ $spec[1] ?? '' }}</span>
                    @endif
                  </li>
                @endforeach
              </ul>
            @endif

            @if ($gh['languages'] !== [])
              <p class="project-langs">
                <span class="project-spec-label">{{ __('Languages', 'sage') }}</span>
                <span class="project-spec-value">{{ implode(' · ', array_slice($gh['languages'], 0, 6)) }}</span>
              </p>
            @endif

            @if ($gh['compatible'] !== '')
              <p class="project-langs">
                <span class="project-spec-label">{{ __('Compatible', 'sage') }}</span>
                <span class="project-spec-value">{{ $gh['compatible'] }}</span>
              </p>
            @endif
          </div>
        </section>
      @endif

      @if ($palette !== [])
        <section class="project-info-section" aria-labelledby="project-palette">
          <h2 id="project-palette" class="display-title is-section">{{ __('Palette', 'sage') }}</h2>
          <ul class="concept-brand-palette">
            @foreach ($palette as $swatch)
              <li>
                <span class="concept-brand-swatch" style="--swatch: {{ esc_attr($swatch[1]) }}"></span>
                <span>{{ $swatch[0] }}</span>
                <code>{{ $swatch[1] }}</code>
              </li>
            @endforeach
          </ul>
        </section>
      @endif

      @if (! empty($tech))
        <section class="project-info-section" aria-labelledby="project-runs-on">
          <h2 id="project-runs-on" class="display-title is-section">{{ __('Runs on', 'sage') }}</h2>
          <p class="pill-row">
            @foreach ($tech as $t)
              <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ $t }}</span>
            @endforeach
          </p>
        </section>
      @endif

      @if ($gh['topics'] !== [])
        <section class="project-info-section" aria-labelledby="project-theme-tags">
          <h2 id="project-theme-tags" class="display-title is-section">{{ __('Theme tags', 'sage') }}</h2>
          <p class="pill-row">
            @foreach ($gh['topics'] as $topic)
              <span class="pill">{{ $topic }}</span>
            @endforeach
          </p>
        </section>
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
        @if ($helloUrl !== '')
          <a class="h-text-arrow" href="{{ esc_url($helloUrl) }}">
            {{ __('Say hello', 'sage') }} →
          </a>
        @endif
      </div>
    </aside>

    @if ($features !== [])
      <section class="project-features" aria-labelledby="project-features">
        <h2 id="project-features" class="display-title is-section">{{ __('What is in this sample', 'sage') }}</h2>
        <ul class="project-feat-grid">
          @foreach ($features as $item)
            <li class="project-feat">
              <span class="project-feat__icon" aria-hidden="true">{!! \App\mh_svg_icon('check', 16) !!}</span>
              <span>{{ $item }}</span>
            </li>
          @endforeach
        </ul>
      </section>
    @endif

    @if ($metrics !== [])
      <section class="project-metrics" aria-labelledby="project-metrics">
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
        <ul class="project-arch-list">
          @foreach (\App\mh_project_prose_list_items($architecture) as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
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
            @if ((string) ($item['q'] ?? $item[0] ?? '') !== '' && (string) ($item['a'] ?? $item[1] ?? '') !== '')
              <details class="project-faq">
                <summary>{{ $item['q'] ?? $item[0] }}</summary>
                <p>{{ $item['a'] ?? $item[1] }}</p>
              </details>
            @endif
          @endforeach
        </div>
      </section>
    @endif
  </div>

  <section class="pf-section pf-section--alt project-talk" aria-labelledby="project-talk-heading">
    <div class="container wide">
      <div class="sec-head">
        <div>
          <p class="eyebrow">{{ __('Visitor feedback', 'sage') }}</p>
          <h2 id="project-talk-heading" class="display-title is-section">{{ __('What would you change?', 'sage') }}</h2>
          <p class="sec-intro">{{ __('Like or star this sample, leave a public note, or write me privately. I read every reply.', 'sage') }}</p>
        </div>
      </div>
      <div class="project-talk__grid">
        <div class="project-talk__notes">
          @php comments_template(); @endphp
        </div>
        <div class="project-talk__ask" id="project-ask">
          <h2 class="display-title is-section">{{ __('Ask about this project', 'sage') }}</h2>
          <p class="sec-intro">{{ __('Name, email, and a few sentences. This goes straight to my inbox.', 'sage') }}</p>
          @include('partials.contact-form', [
            'compact' => true,
            'projectSlug' => $projectSlug,
            'projectTitle' => $title,
            'actionUrl' => $permalink,
          ])
        </div>
      </div>
    </div>
  </section>

  @if ($related !== [])
    <section class="pf-section" aria-labelledby="related-projects">
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
    'label' => __('Ask about this', 'sage'),
    'href' => '#project-ask',
    'secondary' => __('Hire me', 'sage'),
    'secondaryHref' => home_url('/hire/'),
  ])
</article>

@if ($slides !== [])
  <script type="application/json" id="pf-gallery-data">{!! json_encode(
    $slides,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
  ) !!}</script>
  <dialog class="pf-lightbox" data-product-lightbox aria-modal="true" aria-labelledby="pf-lightbox-caption">
    <div class="pf-lightbox__frame">
      <button type="button" class="pf-lightbox__close" data-lightbox-close aria-label="{{ __('Close screenshot', 'sage') }}">
        <span aria-hidden="true">×</span>
      </button>
      @if (count($slides) > 1)
        <button type="button" class="pf-lightbox__nav pf-lightbox__nav--prev" data-lightbox-prev aria-label="{{ __('Previous screenshot', 'sage') }}">
          <span aria-hidden="true">‹</span>
        </button>
      @endif
      <figure class="pf-lightbox__figure">
        <img src="" alt="" width="1600" height="1000" data-lightbox-image>
        <figcaption class="pf-lightbox__caption" id="pf-lightbox-caption" data-lightbox-caption></figcaption>
      </figure>
      @if (count($slides) > 1)
        <button type="button" class="pf-lightbox__nav pf-lightbox__nav--next" data-lightbox-next aria-label="{{ __('Next screenshot', 'sage') }}">
          <span aria-hidden="true">›</span>
        </button>
      @endif
    </div>
  </dialog>
@endif
