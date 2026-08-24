@php
  $posts   = \App\mh_latest_posts(4);
  $work    = array_slice(\App\mh_work_page_items(), 0, 4);
  $gh      = \App\Github::fetchUser(\App\mh_github_login());
  $ghUrl   = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');

  $skills = [
    ['WordPress',   'wordpress',  '#2271b1'],
    ['PHP',         'php',        '#7a86b8'],
    ['Sage / Blade','sage',       '#e3342f'],
    ['Plugins',     'plugins',    '#2271b1'],
    ['JavaScript',  'javascript', '#f7df1e'],
    ['TypeScript',  'typescript', '#3178c6'],
    ['React',       'react',      '#61dafb'],
    ['Tailwind',    'tailwind',   '#38bdf8'],
    ['Vite',        'vite',       '#646cff'],
    ['HTML & CSS',  'html',       '#e34c26'],
    ['Git',         'git',        '#f05032'],
    ['GitHub',      'github',     '#333'],
    ['Power Apps',  'power-apps', '#742774'],
    ['Power Automate','power-automate','#0066ff'],
  ];
@endphp

{{-- ════════════════════════════════════════════════════════════════════════
     01 — HERO
     ════════════════════════════════════════════════════════════════════════ --}}
<section class="h-hero" aria-labelledby="h-hero-name">
  <div class="container wide h-hero__inner">

    <div class="h-hero__copy">
      <div class="h-hero__badges">
        <span class="h-badge">
          {!! \App\mh_svg_icon('map', 14) !!}
          {{ \App\field('home_kicker', $gh['location'] ?: __('Gettysburg, PA', 'sage')) }}
        </span>
        @if (! empty($gh['hireable']))
          <span class="h-badge h-badge--open">
            <span class="h-badge__dot" aria-hidden="true"></span>
            {{ __('Available for work', 'sage') }}
          </span>
        @endif
      </div>

      <h1 id="h-hero-name" class="h-hero__name">
        {{ \App\field('home_h1', $gh['name'] ?: __('Matt Hummel', 'sage')) }}
      </h1>

      <p class="h-hero__role">{{ \App\field('home_role', __('WordPress developer. I mostly build websites.', 'sage')) }}</p>

      <p class="h-hero__lede">
        {{ \App\field('home_lede', __('I build WordPress sites and plugins from Gettysburg, PA. Shops get something they actually own. Developers can read the code.', 'sage')) }}
      </p>

      <div class="h-hero__actions">
        <a class="btn h-hero__cta" href="{{ esc_url(\App\field_href('home_cta_primary_url', '/contact/')) }}">
          {!! \App\mh_svg_icon('mail', 18) !!}
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
              <dt><a href="{{ esc_url($ghUrl.'?tab=repositories') }}" rel="me noopener" target="_blank">{{ number_format_i18n((int) $gh['public_repos']) }}</a></dt>
              <dd>public repos</dd>
            </div>
          @endif
          @if (! empty($gh['followers']))
            <div>
              <dt><a href="{{ esc_url($ghUrl.'?tab=followers') }}" rel="me noopener" target="_blank">{{ number_format_i18n((int) $gh['followers']) }}</a></dt>
              <dd>followers</dd>
            </div>
          @endif
          <div>
            <dt>Gettysburg</dt>
            <dd>Pennsylvania</dd>
          </div>
        </dl>
      @endif

      <nav class="h-quick" aria-label="{{ __('Quick links', 'sage') }}">
        <a href="{{ $writing }}">{!! \App\mh_svg_icon('pen', 15) !!} Journal</a>
        <a href="{{ home_url('/code/') }}">{!! \App\mh_svg_icon('code', 15) !!} Code</a>
        <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">{!! \App\mh_svg_icon('github', 15) !!} GitHub</a>
        <a href="{{ home_url('/about/') }}">{!! \App\mh_svg_icon('user', 15) !!} About</a>
      </nav>
    </div>

    <div class="h-hero__photo-wrap" aria-hidden="true">
      @include('partials.profile-photo', [
        'size'       => 380,
        'class'      => 'profile-photo h-hero__photo',
        'eager'      => true,
        'decorative' => true,
      ])
      <div class="h-hero__photo-ring"></div>
    </div>

  </div>
</section>

{{-- ════════════════════════════════════════════════════════════════════════
     02 — ABOUT STRIP
     ════════════════════════════════════════════════════════════════════════ --}}
<section class="h-about" aria-labelledby="h-about-heading">
  <div class="container wide h-about__inner">

    <div class="h-about__num" aria-hidden="true">02</div>

    <div class="h-about__body">
      <p class="h-section-label">About me</p>
      <h2 id="h-about-heading" class="h-about__heading">
        {{ \App\field('home_about_h2', __('Based in Gettysburg, PA.', 'sage')) }}
      </h2>
      <p class="h-about__text">
        {{ \App\field('home_about_text', __('I\'ve been building for the web since the higher-ed marketing days. WordPress stuck because it lets shops own their content and developers read real code. I still do some Power Platform work when a team lives in Microsoft 365, but WordPress is what I reach for.', 'sage')) }}
      </p>
      <a class="h-text-arrow" href="{{ home_url('/about/') }}">More about me →</a>
    </div>

    <div class="h-about__photo">
      @include('partials.profile-photo', [
        'size'       => 200,
        'class'      => 'profile-photo h-about__img',
        'eager'      => false,
        'decorative' => true,
      ])
    </div>

  </div>
</section>

{{-- ════════════════════════════════════════════════════════════════════════
     03 — SKILLS
     ════════════════════════════════════════════════════════════════════════ --}}
<section class="h-skills" aria-labelledby="h-skills-heading">
  <div class="container wide">
    <div class="h-skills__head">
      <div class="h-about__num" aria-hidden="true">03</div>
      <div>
        <p class="h-section-label">Skills</p>
        <h2 id="h-skills-heading" class="h-section__title">{{ \App\field('home_build_h2', __('What I work with', 'sage')) }}</h2>
      </div>
    </div>

    <div class="h-skill-grid" role="list">
      @foreach ($skills as [$label, $icon, $color])
        <div class="h-skill-tile" role="listitem">
          <span class="h-skill-tile__icon" aria-hidden="true" style="--skill-color: {{ $color }}">
            {!! \App\mh_svg_icon($icon, 28) !!}
          </span>
          <span class="h-skill-tile__name">{{ $label }}</span>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ════════════════════════════════════════════════════════════════════════
     04 — SELECTED WORK (bento grid)
     ════════════════════════════════════════════════════════════════════════ --}}
@if (! empty($work))
<section class="h-section h-section--tinted" aria-labelledby="h-work-heading">
  <div class="container wide">
    <div class="h-section__head">
      <div style="display:flex;align-items:baseline;gap:1rem">
        <span class="h-about__num" aria-hidden="true">04</span>
        <div>
          <p class="h-section-label" style="margin-bottom:.4rem">Projects</p>
          <h2 id="h-work-heading" class="h-section__title" style="margin:0">{{ \App\field('home_work_h2', __('Selected work', 'sage')) }}</h2>
        </div>
      </div>
      <a class="h-text-arrow" href="{{ home_url('/projects/') }}">All projects →</a>
    </div>

    <div class="h-bento">
      @foreach ($work as $i => $p)
        <article class="h-bento__card{{ $i === 0 ? ' h-bento__card--featured' : '' }}" id="work-{{ $p['slug'] }}">
          <a class="h-bento__link" href="{{ home_url('/projects/') }}#{{ $p['slug'] }}" tabindex="-1" aria-hidden="true"></a>

          @if (! empty($p['image']))
            <div class="h-bento__visual">
              <img src="{{ esc_url($p['image']) }}" alt="{{ esc_attr($p['title']) }}" width="{{ $i === 0 ? 900 : 500 }}" height="{{ $i === 0 ? 500 : 300 }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" decoding="async">
            </div>
          @else
            <div class="h-bento__visual h-bento__visual--text">
              <span>{{ $p['title'] }}</span>
            </div>
          @endif

          <div class="h-bento__body">
            <p class="h-label">{{ $p['cat'] }} · {{ $p['place'] }}</p>
            <h3><a href="{{ home_url('/projects/') }}#{{ $p['slug'] }}">{{ $p['title'] }}</a></h3>
            <p class="h-bento__blurb">{{ $p['blurb'] }}</p>
            @if (! empty($p['tech']))
              <div class="h-bento__pills pill-row">
                @foreach (array_slice($p['tech'], 0, 4) as $t)
                  <span class="pill">{!! \App\mh_svg_icon($t, 12) !!} {{ $t }}</span>
                @endforeach
              </div>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ════════════════════════════════════════════════════════════════════════
     05 — FROM THE JOURNAL (featured + cards)
     ════════════════════════════════════════════════════════════════════════ --}}
<section class="h-section" aria-labelledby="h-writing-heading">
  <div class="container wide">
    <div class="h-section__head">
      <div style="display:flex;align-items:baseline;gap:1rem">
        <span class="h-about__num" aria-hidden="true">05</span>
        <div>
          <p class="h-section-label" style="margin-bottom:.4rem">Journal</p>
          <h2 id="h-writing-heading" class="h-section__title" style="margin:0">{{ \App\field('home_write_h2', __('From the journal', 'sage')) }}</h2>
        </div>
      </div>
      <a class="h-text-arrow" href="{{ $writing }}">All posts →</a>
    </div>

    @if (! empty($posts))
      {{-- Featured post --}}
      @php($featured = $posts[0])
      <article class="h-post-featured">
        @if (! empty($featured['thumb']))
          <div class="h-post-featured__visual">
            <img src="{{ esc_url($featured['thumb']) }}" alt="{{ esc_attr($featured['title']) }}" width="960" height="540" loading="lazy" decoding="async">
          </div>
        @else
          <div class="h-post-featured__visual h-post-featured__visual--text">
            <span>{{ wp_trim_words($featured['title'], 5, '') }}</span>
          </div>
        @endif
        <div class="h-post-featured__body">
          <div class="h-post-featured__meta">
            @if ($featured['cat'])
              <span class="h-post-cat">{{ $featured['cat'] }}</span>
            @endif
            <span class="h-post-date">{{ $featured['date'] }}</span>
            @if (! empty($featured['minutes']))
              <span class="h-post-min">{{ sprintf(_n('%d min read', '%d min read', $featured['minutes'], 'sage'), $featured['minutes']) }}</span>
            @endif
          </div>
          <h3 class="h-post-featured__title">
            <a href="{{ esc_url($featured['url']) }}">{{ $featured['title'] }}</a>
          </h3>
          <p class="h-post-featured__ex">{{ $featured['ex'] }}</p>
          <a class="h-post-featured__link" href="{{ esc_url($featured['url']) }}">
            Read post <span aria-hidden="true">→</span>
          </a>
        </div>
      </article>

      {{-- Remaining posts as cards --}}
      @if (count($posts) > 1)
        <div class="h-post-cards">
          @foreach (array_slice($posts, 1) as $post)
            <article class="h-post-card">
              @if (! empty($post['thumb']))
                <div class="h-post-card__visual">
                  <img src="{{ esc_url($post['thumb']) }}" alt="{{ esc_attr($post['title']) }}" width="640" height="360" loading="lazy" decoding="async">
                </div>
              @else
                <div class="h-post-card__visual h-post-card__visual--text">
                  <span>{{ wp_trim_words($post['title'], 4, '') }}</span>
                </div>
              @endif
              <div class="h-post-card__body">
                <div class="h-post-card__meta">
                  @if ($post['cat'])
                    <span class="h-post-cat">{{ $post['cat'] }}</span>
                  @endif
                  <span class="h-post-date">{{ $post['date'] }}</span>
                </div>
                <h3 class="h-post-card__title">
                  <a href="{{ esc_url($post['url']) }}">{{ $post['title'] }}</a>
                </h3>
                <p class="h-post-card__ex">{{ $post['ex'] }}</p>
              </div>
            </article>
          @endforeach
        </div>
      @endif
    @else
      <p>{{ \App\field('home_write_empty', __('New posts coming soon.', 'sage')) }}</p>
    @endif
  </div>
</section>

{{-- ════════════════════════════════════════════════════════════════════════
     CTA
     ════════════════════════════════════════════════════════════════════════ --}}
<section class="h-cta" aria-labelledby="h-cta-heading">
  <div class="container wide h-cta__inner">
    <div>
      <h2 id="h-cta-heading" class="h-cta__heading">{{ \App\field('home_help_h2', __('Working on something?', 'sage')) }}</h2>
      <p class="h-cta__body">{!! \App\field_html('home_help_p2', __('Say hello. A question about a post is just as welcome as a project.', 'sage')) !!}</p>
    </div>
    <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 18) !!}
      {{ \App\field('home_link_hello', __('Say hello', 'sage')) }}
    </a>
  </div>
</section>
