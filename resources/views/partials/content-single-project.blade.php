{{-- Single project at /projects/{slug}/ — landing page order: hero, screenshots, what you get, story, under the hood, questions, feedback. --}}
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
  $demo = (string) ($story['demo'] !== '' ? $story['demo'] : ($card['demo'] ?? ''));
  if ($demo === '' && $gh['homepage'] !== '') {
    $demo = (string) $gh['homepage'];
  }
  $github = (string) ($gh['url'] !== '' ? $gh['url'] : ($card['github'] ?? $card['concept'] ?? ''));
  $hasGithub = $github !== '' && str_starts_with($github, 'http');
  $projectsUrl = home_url('/projects/');
  $related = \App\mh_related_concept_cards($card, 3);
  $summary = (string) ($story['summary'] !== '' ? $story['summary'] : ($card['blurb'] ?? ''));
  if ($summary === '' && $gh['desc'] !== '') {
    $summary = (string) $gh['desc'];
  }
  $features = \App\mh_project_feature_items($story);
  $featureLimit = 10;
  $featuresLead = array_slice($features, 0, $featureLimit);
  $featuresMore = array_slice($features, $featureLimit);
  $architecture = (string) ($case['architecture'] !== '' ? $case['architecture'] : ($docs['architecture'] ?? ''));
  $handoff = (string) ($case['handoff'] !== '' ? $case['handoff'] : ($docs['handoff'] ?? ''));
  $audience = (string) ($docs['audience'] ?? '');
  $faq = is_array($story['faq'] ?? null) ? $story['faq'] : [];
  if ($faq === [] && is_array($docs['faq'] ?? null)) {
    $faq = $docs['faq'];
  }
  $metrics = is_array($story['metrics'] ?? null) ? $story['metrics'] : [];
  $proof = \App\mh_project_proof_facts($metrics, $slides, $features, $gh);
  $specRows = \App\mh_project_spec_rows($card, $gh, $demo, $github);
  $heroImage = (string) ($slides[0]['src'] ?? '');
  $heroImageAlt = (string) ($slides[0]['alt'] ?? sprintf(__('Screenshot of %s', 'sage'), $title));
  $palette = function_exists('\\App\\mh_project_brand_palette_pairs')
    ? \App\mh_project_brand_palette_pairs($postId)
    : [];
  $tagline = trim((string) get_post_meta($postId, '_mh_project_brand_tagline', true));
  $projectSlug = (string) ($card['slug'] ?? get_post_field('post_name', $postId));
  $permalink = (string) get_permalink($postId);
  $buyUrl = (string) ($card['buy_url'] ?? '');
  $buyLabel = (string) ($card['buy_label'] ?? '');
  $priceLabel = (string) ($card['price_label'] ?? '');
  $storySteps = array_values(array_filter([
    ['title' => __('Why I built it', 'sage'), 'text' => (string) ($story['challenge'] ?? '')],
    ['title' => __('What I did', 'sage'), 'text' => (string) ($story['approach'] ?? '')],
    ['title' => __('What you can use', 'sage'), 'text' => (string) ($story['result'] ?? '')],
  ], static fn ($step) => trim($step['text']) !== ''));
  $hasWhatYouGet = $features !== [] || $audience !== '';
  $hasHood = $specRows !== [] || $architecture !== '' || $handoff !== '' || $palette !== [];
  $askUrl = '#project-ask';
@endphp

<article {!! post_class('concept-page project-page') !!}>
  @component('partials.page-hero', ['extra' => 'page-header--project', 'image' => $heroImage, 'imageAlt' => $heroImageAlt])
    <div class="project-hero-head">
      @include('partials.project-type-row', ['p' => $card])
      <h1 class="display-title is-hero">{{ $title }}</h1>
    </div>
    @if ($tagline !== '')
      <p class="project-hero-tagline">{{ $tagline }}</p>
    @endif
    @if ($summary !== '')
      <p class="lead">{{ $summary }}</p>
    @endif

    @if ($proof !== [])
      <ul class="project-proof" aria-label="{{ __('At a glance', 'sage') }}">
        @foreach ($proof as $fact)
          <li class="project-proof__item">
            <strong>{{ $fact[0] }}</strong>
            <span>{{ $fact[1] }}</span>
          </li>
        @endforeach
      </ul>
    @endif

    <div class="concept-hero-actions project-hero-actions">
      @if ($buyUrl !== '')
        <a class="btn" href="{{ esc_url($buyUrl) }}">
          {!! \App\mh_svg_icon($priceLabel === __('Free', 'sage') ? 'download' : 'cart', 15) !!}
          {{ $buyLabel !== '' ? $buyLabel : __('Get the pack', 'sage') }}
        </a>
        @if ($demo !== '')
          <a class="btn btn-outline" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
            {!! \App\mh_svg_icon('globe', 15) !!}
            {{ __('Live demo', 'sage') }}<span class="visually-hidden">{{ __(' (opens in a new window)', 'sage') }}</span> <span aria-hidden="true">↗</span>
          </a>
        @endif
        <a class="h-text-arrow" href="{{ $askUrl }}">{{ __('Ask about this', 'sage') }} <span aria-hidden="true">→</span></a>
      @elseif ($demo !== '')
        <a class="btn" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
          {!! \App\mh_svg_icon('globe', 15) !!}
          {{ __('Live demo', 'sage') }}<span class="visually-hidden">{{ __(' (opens in a new window)', 'sage') }}</span> <span aria-hidden="true">↗</span>
        </a>
        <a class="btn btn-outline" href="{{ $askUrl }}">
          {!! \App\mh_svg_icon('mail', 15) !!}
          {{ __('Ask about this', 'sage') }}
        </a>
      @else
        <a class="btn" href="{{ $askUrl }}">
          {!! \App\mh_svg_icon('mail', 15) !!}
          {{ __('Ask about this', 'sage') }}
        </a>
      @endif
      @if ($hasGithub)
        <a class="h-text-arrow" href="{{ esc_url($github) }}" rel="noopener" target="_blank">
          {{ __('View code', 'sage') }}<span class="visually-hidden">{{ __(' (opens in a new window)', 'sage') }}</span> <span aria-hidden="true">↗</span>
        </a>
      @endif
    </div>

    <p class="project-hero-meta">
      @if ($gh['repo'] !== '')
        <span class="project-hero-meta__repo">{!! \App\mh_svg_icon('github', 13) !!} {{ $gh['owner'] }}/{{ $gh['repo'] }}</span>
      @endif
      @if (! empty($case['notice']))
        <span class="project-hero-meta__note" role="note">{!! \App\mh_svg_icon('shield', 13) !!} {{ $case['notice'] }}</span>
      @endif
    </p>
  @endcomponent

@php
  $projectPills = [];
  if ($slides !== []) {
    $projectPills[] = ['screenshots', __('Screenshots', 'sage')];
  }
  if ($hasWhatYouGet) {
    $projectPills[] = ['project-what-you-get', __('What you get', 'sage')];
  }
  if ($storySteps !== []) {
    $projectPills[] = ['project-story', __('Story', 'sage')];
  }
  if ($hasHood) {
    $projectPills[] = ['project-under-hood', __('Under the hood', 'sage')];
  }
  if ($faq !== []) {
    $projectPills[] = ['project-faq', __('Questions', 'sage')];
  }
  $projectPills[] = ['project-feedback', __('Feedback', 'sage')];
@endphp
@include('partials.page-nav', ['pills' => $projectPills])

  <div class="container wide page-block project-stage project-stage--landing">
    @if ($slides !== [])
      <section class="project-section" id="screenshots" aria-labelledby="project-screenshots-heading">
        <div class="project-section__head">
          <p class="eyebrow">{{ __('Screenshots', 'sage') }}</p>
          <h2 id="project-screenshots-heading" class="display-title is-section">{{ __('What it looks like', 'sage') }}</h2>
        </div>
        <div class="project-stage__gallery pf-product-gallery" data-product-gallery>
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
        </div>
      </section>
    @endif

    @if ($hasWhatYouGet)
      <section class="project-section project-features" id="project-what-you-get" aria-labelledby="project-features-heading">
        <div class="project-section__head">
          <p class="eyebrow">{{ __('What you get', 'sage') }}</p>
          <h2 id="project-features-heading" class="display-title is-section">{{ __('Everything in this build', 'sage') }}</h2>
          @if ($audience !== '')
            <p class="project-section__intro">{{ $audience }}</p>
          @endif
        </div>
        @if ($featuresLead !== [])
          <ul class="project-feat-grid">
            @foreach ($featuresLead as $item)
              <li class="project-feat">{{ $item }}</li>
            @endforeach
          </ul>
        @endif
        @if ($featuresMore !== [])
          <details class="project-feat-more">
            <summary>
              <span class="project-feat-more__closed">{{ sprintf(__('Show all %d features', 'sage'), count($features)) }}</span>
              <span class="project-feat-more__open">{{ __('Show fewer', 'sage') }}</span>
            </summary>
            <ul class="project-feat-grid">
              @foreach ($featuresMore as $item)
                <li class="project-feat">{{ $item }}</li>
              @endforeach
            </ul>
          </details>
        @endif
      </section>
    @endif

    @if ($storySteps !== [])
      <section class="project-section project-story" id="project-story" aria-labelledby="project-story-heading">
        <div class="project-section__head">
          <p class="eyebrow">{{ __('Story', 'sage') }}</p>
          <h2 id="project-story-heading" class="display-title is-section">{{ __('How it came together', 'sage') }}</h2>
        </div>
        <ol class="project-story__steps">
          @foreach ($storySteps as $i => $step)
            <li class="project-story__step">
              <span class="project-story__num" aria-hidden="true">{{ sprintf('%02d', $i + 1) }}</span>
              <h3 class="project-story__title">{{ $step['title'] }}</h3>
              <p class="project-story__body">{{ $step['text'] }}</p>
            </li>
          @endforeach
        </ol>
      </section>
    @endif

    @if ($hasHood)
      <section class="project-section project-hood" id="project-under-hood" aria-labelledby="project-hood-heading">
        <div class="project-section__head">
          <p class="eyebrow">{{ __('Under the hood', 'sage') }}</p>
          <h2 id="project-hood-heading" class="display-title is-section">{{ __('Stack, architecture, and handoff', 'sage') }}</h2>
        </div>
        <div class="project-hood__grid">
          @if ($specRows !== [])
            <div class="project-hood__col">
              <h3 class="project-hood__title">{{ __('Specs', 'sage') }}</h3>
              <div class="project-spec-block">
                <ul class="project-spec-list">
                  @foreach ($specRows as $row)
                    <li>
                      <span class="project-spec-label">{{ $row['label'] }}</span>
                      @if ($row['pills'] !== [])
                        <span class="project-spec-value pill-row project-spec-pills">
                          @foreach ($row['pills'] as $t)
                            <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ $t }}</span>
                          @endforeach
                        </span>
                      @elseif ($row['href'] !== '')
                        <a class="project-spec-value" href="{{ esc_url($row['href']) }}" rel="noopener" target="_blank">{{ $row['value'] }}<span class="visually-hidden">{{ __(' (opens in a new window)', 'sage') }}</span></a>
                      @else
                        <span class="project-spec-value">{{ $row['value'] }}</span>
                      @endif
                    </li>
                  @endforeach
                </ul>
              </div>
            </div>
          @endif

          @if ($architecture !== '')
            <div class="project-hood__col project-detail-block" id="project-architecture">
              <h3 class="project-hood__title">{{ __('Architecture', 'sage') }}</h3>
              <ul class="project-arch-list">
                @foreach (\App\mh_project_prose_list_items($architecture) as $item)
                  <li>{{ $item }}</li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>

        @if ($palette !== [] || $handoff !== '')
          <div class="project-hood__grid project-hood__grid--extra">
            @if ($handoff !== '')
              <div class="project-hood__col project-detail-block" id="project-handoff">
                <h3 class="project-hood__title">{{ __('Handoff', 'sage') }}</h3>
                @foreach (\App\mh_project_prose_paragraphs($handoff) as $para)
                  <p>{{ $para }}</p>
                @endforeach
              </div>
            @endif
            @if ($palette !== [])
              <div class="project-hood__col" id="project-palette">
                <h3 class="project-hood__title">{{ __('Palette', 'sage') }}</h3>
                <ul class="concept-brand-palette project-hood__palette">
                  @foreach ($palette as $swatch)
                    <li>
                      <span class="concept-brand-swatch" style="--swatch: {{ esc_attr($swatch[1]) }}"></span>
                      <span>{{ $swatch[0] }}</span>
                      <code>{{ $swatch[1] }}</code>
                    </li>
                  @endforeach
                </ul>
              </div>
            @endif
          </div>
        @endif
      </section>
    @endif

    @if ($faq !== [])
      <section class="project-section project-detail-block" id="project-faq" aria-labelledby="project-faq-heading">
        <div class="project-section__head">
          <p class="eyebrow">{{ __('Questions', 'sage') }}</p>
          <h2 id="project-faq-heading" class="display-title is-section">{{ __('Common questions', 'sage') }}</h2>
        </div>
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

  {{-- Structured data: FAQPage for the questions above, SoftwareApplication for themes and plugins
       (an Offer only when the project is for sale with a price). --}}
  @php
    $ldFaq = array_values(array_filter(array_map(
      static fn ($item) => ((string) ($item['q'] ?? $item[0] ?? '') !== '' && (string) ($item['a'] ?? $item[1] ?? '') !== '')
        ? ['@type' => 'Question', 'name' => wp_strip_all_tags((string) ($item['q'] ?? $item[0])), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_strip_all_tags((string) ($item['a'] ?? $item[1]))]]
        : null,
      $faq
    )));
    $ldType = sanitize_key((string) get_post_meta($postId, '_mh_project_product_type', true));
    $ldGraph = [];
    if (in_array($ldType, ['theme', 'plugin'], true)) {
      $ldPrice = trim((string) get_post_meta($postId, '_mh_project_price', true));
      $ldApp = array_filter([
        '@type' => 'SoftwareApplication',
        '@id' => get_permalink($postId).'#software',
        'name' => $title,
        'description' => wp_strip_all_tags($summary),
        'url' => get_permalink($postId),
        'image' => $heroImage !== '' ? $heroImage : null,
        'screenshot' => array_values(array_filter(array_map(static fn ($slide) => (string) ($slide['src'] ?? ''), array_slice($slides, 0, 6)))),
        'applicationCategory' => $ldType === 'plugin' ? 'BusinessApplication' : 'WebApplication',
        'applicationSubCategory' => $ldType === 'plugin' ? 'WordPress plugin' : 'WordPress theme',
        'operatingSystem' => 'WordPress',
        'softwareVersion' => trim((string) get_post_meta($postId, '_mh_project_version', true)) ?: null,
        'softwareRequirements' => trim((string) get_post_meta($postId, '_mh_project_compatible', true)) ?: null,
        'license' => trim((string) get_post_meta($postId, '_mh_project_license', true)) ?: null,
        'author' => ['@type' => 'Person', 'name' => 'Matt Hummel', 'url' => home_url('/')],
        'sameAs' => array_values(array_filter([$github, $demo])),
        'offers' => \App\mh_project_is_for_sale($postId) && is_numeric($ldPrice)
          ? ['@type' => 'Offer', 'price' => $ldPrice, 'priceCurrency' => 'USD', 'availability' => 'https://schema.org/InStock', 'url' => get_permalink($postId)]
          : null,
      ], static fn ($value) => $value !== null && $value !== '' && $value !== []);
      $ldGraph[] = $ldApp;
    }
    if ($ldFaq !== []) {
      $ldGraph[] = ['@type' => 'FAQPage', '@id' => get_permalink($postId).'#faq', 'mainEntity' => $ldFaq];
    }
    $ldJson = $ldGraph !== [] ? json_encode(['@context' => 'https://schema.org', '@graph' => $ldGraph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) : '';
  @endphp
  @if ($ldJson)
    <script type="application/ld+json">{!! $ldJson !!}</script>
  @endif

  <section class="pf-section pf-section--alt project-talk" id="project-feedback" aria-labelledby="project-talk-heading">
    <div class="container wide">
      <div class="sec-head">
        <div>
          <p class="eyebrow">{{ __('Feedback', 'sage') }}</p>
          <h2 id="project-talk-heading" class="display-title is-section">{{ __('What would you change?', 'sage') }}</h2>
          <p class="sec-intro">{{ __('Like or star this sample, leave a public note, or write me privately. I read every reply.', 'sage') }}</p>
        </div>
        <button type="button" class="h-text-arrow post-copy-link project-talk__copy" data-copy="{{ esc_url($permalink) }}">
          {!! \App\mh_svg_icon('share', 14) !!}
          <span>{{ __('Copy link', 'sage') }}</span>
        </button>
      </div>
      <div class="project-talk__grid">
        <div class="project-talk__notes">
          <div class="project-talk__react">
            @include('partials.project-react', ['postId' => $postId, 'github' => $github])
          </div>
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
    'href' => $askUrl,
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
