@php
  $posts = \App\mh_home_journal_posts(5);
  $work  = array_slice(\App\mh_work_page_items(), 0, 4);
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
@endphp

{{-- Hero --}}
<section class="h-hero" aria-labelledby="h-hero-name">
  <div class="h-hero__atmosphere" aria-hidden="true">
    <span class="h-hero__blob h-hero__blob--a" data-parallax="0.1"></span>
    <span class="h-hero__blob h-hero__blob--b" data-parallax="0.06"></span>
    <span class="h-hero__mesh"></span>
  </div>
  <div class="container wide h-hero__inner">

    <div class="h-hero__copy">
      <p class="h-hero__kicker">
        <span class="h-hero__kicker-dot" aria-hidden="true"></span>
        {{ \App\field('home_kicker', __('WordPress · plugins · web apps', 'sage')) }}
      </p>

      <h1 id="h-hero-name" class="h-hero__name">
        {{ \App\field('home_h1', \App\mh_home_hero_default('h1')) }}
      </h1>

      <p class="h-hero__role">
        {{ \App\field('home_role', \App\mh_home_hero_default('role')) }}
      </p>

      <p class="h-hero__lede">
        {{ \App\field('home_lede', \App\mh_home_hero_default('lede')) }}
      </p>

      <div class="h-hero__actions">
        <a class="btn h-hero__cta" href="{{ esc_url(\App\field_href('home_cta_primary_url', '/hire/')) }}">
          {!! \App\mh_svg_icon('mail', 17) !!}
          {{ \App\field('home_cta_primary', __('Hire me', 'sage')) }}
        </a>
        <a class="h-text-arrow" href="{{ esc_url(\App\field_href('home_cta_secondary_url', '/projects/')) }}">
          {{ \App\field('home_cta_secondary', __('Browse projects', 'sage')) }}
          <span aria-hidden="true">→</span>
        </a>
      </div>
    </div>

    <aside class="h-hero__viz" aria-label="{{ __('Selected work preview', 'sage') }}">
      <div class="h-hero-work">
        @php $fp = $work[0] ?? null; @endphp
        @if ($fp)
          <div class="h-hero-work__frame">
            <div class="h-hero-work__chrome" aria-hidden="true">
              <span class="h-hero-work__dot"></span>
              <span class="h-hero-work__dot"></span>
              <span class="h-hero-work__dot"></span>
              <span class="h-hero-work__addr">matthummel.com/projects</span>
            </div>
            <a class="h-hero-work__main-link"
               href="{{ esc_url($fp['url'] ?? \App\mh_work_listing_url()) }}"
               aria-label="{{ esc_attr(__('View ', 'sage').($fp['title'] ?? '').__(' project', 'sage')) }}">
              @if (! empty($fp['image']))
                <img
                  class="h-hero-work__main-img"
                  src="{{ esc_url($fp['image']) }}"
                  alt="{{ esc_attr(($fp['title'] ?? '').' — '.($fp['cat'] ?? '').' website') }}"
                  width="640"
                  height="400"
                  loading="eager"
                  decoding="async"
                >
              @else
                <div class="h-hero-work__main-img h-hero-work__main-img--text">
                  {{ $fp['title'] ?? '' }}
                </div>
              @endif
              <span class="h-hero-work__main-overlay" aria-hidden="true">
                <span class="h-hero-work__main-name">{{ $fp['title'] ?? '' }}</span>
                <span class="h-hero-work__main-cat">{{ $fp['cat'] ?? '' }}</span>
              </span>
            </a>
          </div>

          @php $miniWork = array_slice($work, 1, 2); @endphp
          @if (! empty($miniWork))
            <div class="h-hero-work__grid">
              @foreach ($miniWork as $pw)
                <a class="h-hero-work__mini"
                   href="{{ esc_url($pw['url'] ?? \App\mh_work_listing_url()) }}"
                   aria-label="{{ esc_attr($pw['title'] ?? '') }}">
                  @if (! empty($pw['image']))
                    <img
                      src="{{ esc_url($pw['image']) }}"
                      alt="{{ esc_attr($pw['title'] ?? '') }}"
                      width="300"
                      height="180"
                      loading="lazy"
                      decoding="async"
                    >
                  @else
                    <span class="h-hero-work__mini-fallback" aria-hidden="true">
                      {{ $pw['title'] ?? '' }}
                    </span>
                  @endif
                  <span class="h-hero-work__mini-label">{{ $pw['title'] ?? '' }}</span>
                </a>
              @endforeach
            </div>
          @endif
        @endif
      </div>
    </aside>
  </div>
</section>

{{-- Projects --}}
@if (! empty($work))
@php
  $allWork = \App\mh_work_page_items();
  $totalProjects = count($allWork);
  $caseWork = \App\mh_home_case_study_cards($allWork, 3);
@endphp
<section class="h-section h-section--tinted h-band h-band--tint" id="work" aria-labelledby="h-work-heading">
  <div class="container wide">
    <div class="h-work-header">
      <div>
        <p class="h-section-label">{{ __('Projects', 'sage') }}</p>
        <h2 id="h-work-heading" class="h-section__title">
          {{ \App\field('home_work_h2', __('Selected projects.', 'sage')) }}
        </h2>
        <p class="h-work-intro">
          {{ \App\field('home_work_intro', __('WordPress themes and plugins I built in public. Open one for a short story and a live demo when I have one.', 'sage')) }}
        </p>
      </div>
      <div class="h-work-header__meta">
        <span class="h-work-count">{{ sprintf(_n('%s project', '%s projects', $totalProjects, 'sage'), number_format_i18n($totalProjects)) }}</span>
        <a class="h-text-arrow" href="{{ esc_url(\App\mh_work_listing_url()) }}">{{ __('Browse all', 'sage') }} →</a>
      </div>
    </div>

    <div class="h-case-list">
      @foreach ($caseWork as $i => $p)
        @include('partials.home-case-card', ['p' => $p, 'featured' => $i === 0])
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- Journal --}}
@php
  $journalFeatured = $posts[0] ?? null;
  $journalStack    = array_slice($posts, 1, 4);
  $rssUrl = home_url('/feed/');
@endphp
<section class="h-journal h-band" id="journal" aria-labelledby="h-writing-heading">
  <div class="container wide">
    <div class="h-journal__head">
      <div class="h-journal__head-copy">
        <p class="h-section-label">{{ __('Journal', 'sage') }}</p>
        <h2 id="h-writing-heading" class="h-section__title">
          {{ \App\field('home_write_h2', __('Recent writing.', 'sage')) }}
        </h2>
        <p class="h-journal__intro">
          {{ \App\field('home_write_intro', __('Notes from WordPress builds — themes, handoffs, and things I want to remember next time.', 'sage')) }}
        </p>
      </div>
      <div class="h-journal__head-links">
        <a class="h-text-arrow" href="{{ $writing }}">{{ __('All posts', 'sage') }} →</a>
        <a class="h-journal__rss" href="{{ esc_url($rssUrl) }}" rel="alternate" type="application/rss+xml">
          {!! \App\mh_svg_icon('rss', 14) !!} RSS
        </a>
      </div>
    </div>

    @if (! empty($posts))
      <div class="h-journal__grid">
        @if ($journalFeatured)
        @php $jp = $journalFeatured; @endphp
        <article class="h-journal__featured" itemscope itemtype="https://schema.org/BlogPosting">
          <meta itemprop="author" content="Matt Hummel">
          <div class="h-journal__badge">
            {!! \App\mh_svg_icon('pen', 13) !!} {{ __('Latest', 'sage') }}
          </div>
          @if (! empty($jp['thumb']))
            <a class="h-journal__featured-img-link" href="{{ esc_url($jp['url']) }}" tabindex="-1" aria-hidden="true">
              <div class="h-journal__featured-img">
                <img
                  src="{{ esc_url($jp['thumb']) }}"
                  alt="{{ esc_attr($jp['title']) }}"
                  width="960" height="540"
                  loading="lazy"
                  decoding="async"
                  itemprop="image"
                >
              </div>
            </a>
          @else
            <a class="h-journal__featured-img-link" href="{{ esc_url($jp['url']) }}" tabindex="-1" aria-hidden="true">
              <div class="h-journal__featured-img h-journal__featured-img--text">
                <span>{{ wp_trim_words($jp['title'], 6, '') }}</span>
              </div>
            </a>
          @endif
          <div class="h-journal__featured-body">
            <div class="h-journal__featured-meta">
              @if ($jp['cat'])
                <a class="h-journal__cat" href="{{ esc_url($jp['cat_url'] ?? $writing) }}" itemprop="articleSection">{{ $jp['cat'] }}</a>
              @endif
              <time class="h-journal__date" datetime="{{ esc_attr($jp['date_iso'] ?? '') }}" itemprop="datePublished">{{ $jp['date'] }}</time>
              @if (! empty($jp['minutes']))
                <span class="h-journal__min">{!! \App\mh_svg_icon('book-open', 13) !!} {{ $jp['minutes'] }} min</span>
              @endif
            </div>
            <h3 class="h-journal__featured-title" itemprop="headline">
              <a href="{{ esc_url($jp['url']) }}">{{ $jp['title'] }}</a>
            </h3>
            <p class="h-journal__featured-ex" itemprop="description">{{ $jp['ex'] }}</p>
            <a class="h-journal__read-link" href="{{ esc_url($jp['url']) }}">
              {{ __('Read post', 'sage') }} <span aria-hidden="true">→</span>
            </a>
          </div>
        </article>
        @endif

        @if (! empty($journalStack))
        <div class="h-journal__stack">
          <p class="h-journal__stack-label">{{ __('More posts', 'sage') }}</p>
          @foreach ($journalStack as $post)
            <article class="h-journal__post{{ ! empty($post['deemphasize']) ? ' h-journal__post--quiet' : '' }}" itemscope itemtype="https://schema.org/BlogPosting">
              <meta itemprop="author" content="Matt Hummel">
              @if (! empty($post['thumb']))
                <a class="h-journal__post-thumb" href="{{ esc_url($post['url']) }}" tabindex="-1" aria-hidden="true">
                  <img src="{{ esc_url($post['thumb']) }}" alt="" width="120" height="80" loading="lazy" decoding="async">
                </a>
              @else
                <div class="h-journal__post-thumb h-journal__post-thumb--text" aria-hidden="true">
                  {!! \App\mh_svg_icon('pen', 18) !!}
                </div>
              @endif
              <div class="h-journal__post-body">
                @if ($post['cat'])
                  <a class="h-journal__cat h-journal__cat--sm" href="{{ esc_url($post['cat_url'] ?? $writing) }}" itemprop="articleSection">{{ $post['cat'] }}</a>
                @endif
                <h3 class="h-journal__post-title" itemprop="headline">
                  <a href="{{ esc_url($post['url']) }}">{{ $post['title'] }}</a>
                </h3>
                <div class="h-journal__post-meta">
                  <time datetime="{{ esc_attr($post['date_iso'] ?? '') }}" itemprop="datePublished">{{ $post['date'] }}</time>
                  @if (! empty($post['minutes']))
                    <span>· {{ $post['minutes'] }} min</span>
                  @endif
                </div>
              </div>
            </article>
          @endforeach
        </div>
        @endif
      </div>
    @else
      <p class="h-journal__empty">{{ \App\field('home_write_empty', __('No posts yet.', 'sage')) }}</p>
    @endif
  </div>
</section>

{{-- Closing CTA --}}
<section class="cta-band h-cta" aria-labelledby="h-cta-heading" data-reveal>
  <div class="container wide cta-band-inner h-cta__inner">
    <div class="cta-band__copy">
      <p class="eyebrow eyebrow--on-dark">{{ __('Get in touch', 'sage') }}</p>
      <h2 id="h-cta-heading" class="display-title is-section h-cta__heading">
        {{ \App\field('home_help_h2', __('Want to work together?', 'sage')) }}
      </h2>
      <p class="h-cta__body">{!! \App\field_html('home_help_p2', sprintf(
        __('Tell me about the role or the site. Background and process are on <a href="%1$s">About</a>. Repos and stack notes are on <a href="%2$s">Code</a>.', 'sage'),
        esc_url(home_url('/about/')),
        esc_url(home_url('/code/'))
      )) !!}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ \App\field('home_link_hello', __('Say hello', 'sage')) }}
      </a>
      <p class="cta-band__note">{{ \App\mh_reply_sla('note') }}</p>
    </div>
  </div>
</section>
