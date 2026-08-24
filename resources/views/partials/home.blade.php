@php
  $posts   = \App\mh_latest_posts(3);
  $work    = array_slice(\App\mh_work_page_items(), 0, 4);
  $gh      = \App\Github::fetchUser(\App\mh_github_login());
  $ghUrl   = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     HERO — white, full height, bold name
     ═══════════════════════════════════════════════════════════════════ --}}
<section class="h-hero" aria-labelledby="h-hero-name">
  <div class="container wide h-hero__inner">

    <div class="h-hero__copy">

      <div class="h-hero__badges">
        <span class="h-badge">{{ \App\field('home_kicker', $gh['location'] ?: __('Gettysburg, PA', 'sage')) }}</span>
        @if (! empty($gh['hireable']))
          <span class="h-badge h-badge--open">{{ __('Available', 'sage') }}</span>
        @endif
      </div>

      <h1 id="h-hero-name" class="h-hero__name">
        {{ \App\field('home_h1', $gh['name'] ?: __('Matt Hummel', 'sage')) }}
      </h1>

      <p class="h-hero__role">
        {{ \App\field('home_role', __('WordPress developer. I mostly build websites.', 'sage')) }}
      </p>

      <p class="h-hero__lede">
        {{ \App\field('home_lede', __('I build WordPress sites and plugins from Gettysburg, PA. Shops get something they actually own — not a subscription they rent. I\'ve done Power Platform work too, but WordPress is what I reach for.', 'sage')) }}
      </p>

      <div class="h-hero__actions">
        <a class="btn" href="{{ esc_url(\App\field_href('home_cta_primary_url', '/contact/')) }}">
          {{ \App\field('home_cta_primary', __('Say hello', 'sage')) }}
        </a>
        <a class="h-text-arrow" href="{{ esc_url(\App\field_href('home_cta_secondary_url', '/projects/')) }}">
          {{ \App\field('home_cta_secondary', __('See my work', 'sage')) }}
          <span aria-hidden="true">→</span>
        </a>
      </div>

      @if (! empty($gh['public_repos']) || ! empty($gh['followers']))
        <dl class="h-stats">
          @if (! empty($gh['public_repos']))
            <div>
              <dt><a href="{{ esc_url($ghUrl.'?tab=repositories') }}" rel="me noopener" target="_blank">{{ number_format_i18n((int) $gh['public_repos']) }}<span class="visually-hidden"> (opens in a new window)</span></a></dt>
              <dd>{{ __('public repos', 'sage') }}</dd>
            </div>
          @endif
          @if (! empty($gh['followers']))
            <div>
              <dt><a href="{{ esc_url($ghUrl.'?tab=followers') }}" rel="me noopener" target="_blank">{{ number_format_i18n((int) $gh['followers']) }}<span class="visually-hidden"> (opens in a new window)</span></a></dt>
              <dd>{{ __('followers', 'sage') }}</dd>
            </div>
          @endif
        </dl>
      @endif

      <nav class="h-quick" aria-label="{{ __('Quick links', 'sage') }}">
        <a href="{{ $writing }}">{{ __('Journal', 'sage') }}</a>
        <a href="{{ home_url('/code/') }}">{{ __('Code', 'sage') }}</a>
        <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">GitHub<span class="visually-hidden"> (opens in a new window)</span></a>
        <a href="{{ home_url('/about/') }}">{{ __('About', 'sage') }}</a>
      </nav>

    </div>

    @include('partials.profile-photo', [
      'size'  => 340,
      'class' => 'profile-photo h-hero__photo',
      'eager' => true,
    ])

  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════
     WHAT I BUILD — 3-column capability strip
     ═══════════════════════════════════════════════════════════════════ --}}
<section class="h-services" aria-labelledby="h-services-heading">
  <div class="container wide">
    <h2 id="h-services-heading" class="h-section-label">{{ \App\field('home_build_h2', __('What I build', 'sage')) }}</h2>
    <div class="h-services__grid">

      <article class="h-service">
        <span class="h-service__icon" aria-hidden="true">{!! \App\mh_svg_icon('globe', 26) !!}</span>
        <h3>{{ \App\field('home_build_1_title', __('WordPress sites', 'sage')) }}</h3>
        <p>{{ \App\field('home_build_1_text', __('Clean, fast, and editable. Shops get something they own — not a subscription they rent.', 'sage')) }}</p>
        <a class="h-service__link" href="{{ home_url('/services/') }}">{{ __('How it works', 'sage') }} →</a>
      </article>

      <article class="h-service">
        <span class="h-service__icon" aria-hidden="true">{!! \App\mh_svg_icon('code', 26) !!}</span>
        <h3>{{ \App\field('home_build_2_title', __('Plugins & tools', 'sage')) }}</h3>
        <p>{{ \App\field('home_build_2_text', __('Custom PHP when WordPress needs a new part. Small, focused, and readable.', 'sage')) }}</p>
        <a class="h-service__link" href="{{ home_url('/code/') }}">{{ __('See the code', 'sage') }} →</a>
      </article>

      <article class="h-service">
        <span class="h-service__icon" aria-hidden="true">{!! \App\mh_svg_icon('box', 26) !!}</span>
        <h3>{{ \App\field('home_build_3_title', __('Other web apps', 'sage')) }}</h3>
        <p>{{ \App\field('home_build_3_text', __('React, APIs, and anything that doesn\'t fit in a theme. Power Platform when a team lives in Microsoft 365.', 'sage')) }}</p>
        <a class="h-service__link" href="{{ home_url('/services/') }}">{{ __('See services', 'sage') }} →</a>
      </article>

    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════
     SELECTED WORK — 2×2 image-forward grid
     ═══════════════════════════════════════════════════════════════════ --}}
@if (! empty($work))
<section class="h-section" aria-labelledby="h-work-heading">
  <div class="container wide">
    <div class="h-section__head">
      <h2 id="h-work-heading" class="h-section__title">{{ \App\field('home_work_h2', __('Selected work', 'sage')) }}</h2>
      <a class="h-text-arrow" href="{{ home_url('/projects/') }}">{{ __('All projects', 'sage') }} →</a>
    </div>
    <div class="h-work-grid">
      @foreach ($work as $p)
        <article class="h-work-card">
          <a class="h-work-card__link" href="{{ home_url('/projects/') }}#{{ $p['slug'] }}" tabindex="-1" aria-hidden="true"></a>
          @if (! empty($p['image']))
            <div class="h-work-card__visual">
              <img src="{{ esc_url($p['image']) }}" alt="{{ esc_attr($p['title']) }}" width="640" height="360" loading="lazy" decoding="async">
            </div>
          @else
            <div class="h-work-card__visual h-work-card__visual--text">
              <span aria-hidden="true">{{ wp_trim_words($p['title'], 3, '') }}</span>
            </div>
          @endif
          <div class="h-work-card__body">
            <p class="h-label">{{ $p['cat'] }} · {{ $p['place'] }}</p>
            <h3><a href="{{ home_url('/projects/') }}#{{ $p['slug'] }}">{{ $p['title'] }}</a></h3>
            <p class="h-work-card__blurb">{{ $p['blurb'] }}</p>
            @if (! empty($p['tech']))
              <p class="pill-row">
                @foreach (array_slice($p['tech'], 0, 3) as $t)
                  <span class="pill">{!! \App\mh_svg_icon($t, 13) !!} {{ $t }}</span>
                @endforeach
              </p>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     FROM THE JOURNAL — clean post list
     ═══════════════════════════════════════════════════════════════════ --}}
<section class="h-section h-section--tinted" aria-labelledby="h-writing-heading">
  <div class="container wide">
    <div class="h-section__head">
      <h2 id="h-writing-heading" class="h-section__title">{{ \App\field('home_write_h2', __('From the journal', 'sage')) }}</h2>
      <a class="h-text-arrow" href="{{ $writing }}">{{ __('All posts', 'sage') }} →</a>
    </div>
    <div class="h-post-list">
      @forelse ($posts as $post)
        <article class="h-post-row">
          <div class="h-post-row__meta">
            <time class="h-post-date">{{ $post['date'] }}</time>
            @if ($post['cat'])
              <span class="h-post-cat">{{ $post['cat'] }}</span>
            @endif
            @if (! empty($post['minutes']))
              <span class="h-post-min">{{ sprintf(_n('%d min', '%d min', $post['minutes'], 'sage'), $post['minutes']) }}</span>
            @endif
          </div>
          <div class="h-post-row__body">
            <h3 class="h-post-row__title"><a href="{{ esc_url($post['url']) }}">{{ $post['title'] }}</a></h3>
            <p class="h-post-row__ex">{{ $post['ex'] }}</p>
          </div>
        </article>
      @empty
        <p>{{ \App\field('home_write_empty', __('New posts coming soon.', 'sage')) }}</p>
      @endforelse
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════
     CTA — dark, simple
     ═══════════════════════════════════════════════════════════════════ --}}
<section class="h-cta" aria-labelledby="h-cta-heading">
  <div class="container wide h-cta__inner">
    <div>
      <h2 id="h-cta-heading" class="h-cta__heading">{{ \App\field('home_help_h2', __('Working on something?', 'sage')) }}</h2>
      <p class="h-cta__body">{!! \App\field_html('home_help_p2', __('Say hello. A question about a post is just as welcome as a project inquiry.', 'sage')) !!}</p>
    </div>
    <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">{{ \App\field('home_link_hello', __('Say hello', 'sage')) }}</a>
  </div>
</section>
