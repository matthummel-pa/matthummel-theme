@php
  $hero = \App\mh_home_hero();
  $posts = \App\mh_home_journal_posts(3);
  $work = \App\mh_home_featured_projects(3);
  $allWork = \App\mh_work_page_items();
  $totalProjects = count($allWork);
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $rssUrl = home_url('/feed/');
  $helpCards = [
    [
      'icon' => 'wordpress',
      'title' => \App\field('home_build_1_title', __('WordPress sites', 'sage')),
      'text' => \App\field('home_build_1_text', __('Custom themes shops can edit in wp-admin. You own the code.', 'sage')),
    ],
    [
      'icon' => 'plugins',
      'title' => \App\field('home_build_2_title', __('Plugins & tools', 'sage')),
      'text' => \App\field('home_build_2_text', __('Small PHP plugins when WordPress needs a new part.', 'sage')),
    ],
    [
      'icon' => 'code',
      'title' => \App\field('home_build_3_title', __('Full-stack web apps', 'sage')),
      'text' => \App\field('home_build_3_text', __('Interfaces, services, and APIs when a theme alone is not enough.', 'sage')),
    ],
  ];
@endphp

{{-- 1. Simplified hero — copy only, no project gallery --}}
<section
  class="h-hero h-hero--simple{{ $hero['accent'] ? ' h-hero--accent' : '' }} h-hero--{{ $hero['align'] }} h-hero--pad-{{ $hero['pad'] }}"
  aria-labelledby="h-hero-name"
  style="--mh-hero-max: {{ esc_attr($hero['max_width']) }};"
>
  <div class="container wide h-hero__inner">
    <div class="h-hero__copy">
      @if ($hero['eyebrow'] !== '')
        <p class="h-hero__kicker">{{ $hero['eyebrow'] }}</p>
      @endif

      <h1 id="h-hero-name" class="h-hero__name">{{ $hero['h1'] }}</h1>

      @if ($hero['role'] !== '')
        <p class="h-hero__role">{{ $hero['role'] }}</p>
      @endif

      @if ($hero['subcopy'] !== '')
        <p class="h-hero__lede">{{ $hero['subcopy'] }}</p>
      @endif

      @if ($hero['show_primary'] || $hero['show_secondary'])
        <div class="h-hero__actions">
          @if ($hero['show_primary'] && $hero['cta_primary'] !== '')
            <a class="btn h-hero__cta" href="{{ esc_url($hero['cta_primary_url']) }}">
              {!! \App\mh_svg_icon('mail', 17) !!}
              {{ $hero['cta_primary'] }}
            </a>
          @endif
          @if ($hero['show_secondary'] && $hero['cta_secondary'] !== '')
            <a class="h-text-arrow" href="{{ esc_url($hero['cta_secondary_url']) }}">
              {{ $hero['cta_secondary'] }}
              <span aria-hidden="true">→</span>
            </a>
          @endif
        </div>
      @endif
    </div>
  </div>
</section>

{{-- 2. Featured Projects CPT --}}
<section class="h-section h-band" id="work" aria-labelledby="h-work-heading">
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
        @if ($totalProjects > 0)
          <span class="h-work-count">{{ sprintf(_n('%s project', '%s projects', $totalProjects, 'sage'), number_format_i18n($totalProjects)) }}</span>
        @endif
        <a class="h-text-arrow" href="{{ esc_url(\App\mh_work_listing_url()) }}">{{ __('Browse all', 'sage') }} →</a>
      </div>
    </div>

    @if ($work !== [])
      <div class="h-project-grid">
        @foreach ($work as $p)
          @include('partials.home-project-card', ['p' => $p])
        @endforeach
      </div>
    @else
      <p class="h-journal__empty">{{ __('Projects are on the way.', 'sage') }}</p>
    @endif
  </div>
</section>

{{-- 3. Latest journal --}}
<section class="h-journal h-band h-band--tint" id="journal" aria-labelledby="h-writing-heading">
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
        <a class="h-text-arrow" href="{{ esc_url($writing) }}">{{ __('All posts', 'sage') }} →</a>
        <a class="h-journal__rss" href="{{ esc_url($rssUrl) }}" rel="alternate" type="application/rss+xml">
          {!! \App\mh_svg_icon('rss', 14) !!} RSS
        </a>
      </div>
    </div>

    @if ($posts !== [])
      <div class="h-journal-list">
        @foreach ($posts as $i => $post)
          <article class="h-journal-item" itemscope itemtype="https://schema.org/BlogPosting">
            <meta itemprop="author" content="Matt Hummel">
            @if (! empty($post['thumb']))
              <a class="h-journal-item__thumb" href="{{ esc_url($post['url']) }}" tabindex="-1" aria-hidden="true">
                <img
                  src="{{ esc_url($post['thumb']) }}"
                  alt=""
                  width="320"
                  height="200"
                  loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                  decoding="async"
                  itemprop="image"
                >
              </a>
            @endif
            <div class="h-journal-item__body">
              <div class="h-journal-item__meta">
                @if ($post['cat'])
                  <a class="h-journal__cat" href="{{ esc_url($post['cat_url'] ?? $writing) }}" itemprop="articleSection">{{ $post['cat'] }}</a>
                @endif
                <time datetime="{{ esc_attr($post['date_iso'] ?? '') }}" itemprop="datePublished">{{ $post['date'] }}</time>
                @if (! empty($post['minutes']))
                  <span>{{ $post['minutes'] }} min</span>
                @endif
              </div>
              <h3 class="h-journal-item__title" itemprop="headline">
                <a href="{{ esc_url($post['url']) }}">{{ $post['title'] }}</a>
              </h3>
              @if (! empty($post['ex']))
                <p class="h-journal-item__ex" itemprop="description">{{ $post['ex'] }}</p>
              @endif
            </div>
          </article>
        @endforeach
      </div>
    @else
      <p class="h-journal__empty">{{ \App\field('home_write_empty', __('No posts yet.', 'sage')) }}</p>
    @endif
  </div>
</section>

{{-- 4. What I do / services icon grid --}}
<section class="h-skills h-band" id="do" aria-labelledby="h-do-heading">
  <div class="container wide">
    <div class="h-skills__head">
      <div>
        <p class="h-section-label">{{ __('Services', 'sage') }}</p>
        <h2 id="h-do-heading" class="h-section__title">
          {{ \App\field('home_build_h2', __('What I do.', 'sage')) }}
        </h2>
      </div>
      <p class="h-skills__note">
        {{ __('For shops and agencies. Stack notes are on About and Code.', 'sage') }}
      </p>
    </div>
    <div class="h-help-grid">
      @foreach ($helpCards as $card)
        <article class="h-help-card">
          <span class="h-help-card__icon" aria-hidden="true">{!! \App\mh_svg_icon($card['icon'], 28) !!}</span>
          <h3 class="h-help-card__title">{{ $card['title'] }}</h3>
          <p class="h-help-card__text">{{ $card['text'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- Quiet close — not a sales band --}}
<section class="h-close" aria-labelledby="h-cta-heading">
  <div class="container wide h-close__inner">
    <div class="h-close__copy">
      <h2 id="h-cta-heading" class="h-section__title">
        {{ \App\field('home_help_h2', __('Want to work together?', 'sage')) }}
      </h2>
      <p class="h-close__body">{!! \App\field_html('home_help_p2', sprintf(
        __('Tell me about the role or the site. Background is on <a href="%1$s">About</a>. Repos are on <a href="%2$s">Code</a>.', 'sage'),
        esc_url(home_url('/about/')),
        esc_url(home_url('/code/'))
      )) !!}</p>
    </div>
    <a class="btn" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!}
      {{ \App\field('home_link_hello', __('Say hello', 'sage')) }}
    </a>
  </div>
</section>
