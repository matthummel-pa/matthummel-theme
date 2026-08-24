@php
  $posts  = \App\mh_latest_posts(3);
  $gh     = \App\Github::fetchUser(\App\mh_github_login());
  $repos  = \App\mh_home_github_repos(4);
  $work   = array_slice(\App\mh_work_page_items(), 0, 4);
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $ghUrl  = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $ghBlog = \App\mh_github_blog_url($gh);
  $stack  = \App\field_lines('home_stack', [
    __('WordPress', 'sage'),
    __('PHP', 'sage'),
    __('Sage / Blade', 'sage'),
    __('JavaScript', 'sage'),
    __('HTML & CSS', 'sage'),
    __('Plugins', 'sage'),
    __('Git', 'sage'),
    __('Power Apps', 'sage'),
    __('Power Automate', 'sage'),
    __('React', 'sage'),
  ]);
@endphp

{{-- ── HERO ── clean white, no gradient --}}
<section class="hero hero--clean" aria-labelledby="hero-heading">
  <div class="container wide hero-inner hero-inner--portfolio">

    <div class="hero-copy">
      <p class="hero-loc">{{ \App\field('home_kicker', $gh['location'] ?: __('Gettysburg, PA', 'sage')) }}</p>

      <h1 id="hero-heading" class="display-title is-hero">
        {{-- strip trailing period so the underline accent sits cleanly --}}
        {{ \App\field('home_h1', $gh['name'] ?: __('Matt Hummel', 'sage')) }}
      </h1>

      <p class="hero-role-clean">{{ \App\field('home_role', __('WordPress developer. I mostly build websites.', 'sage')) }}</p>

      <p class="hero-lede-clean">{{ \App\field('home_lede', __('I build WordPress sites, plugins, and other web apps. Mostly WordPress — it\'s what I enjoy. Shops get something they can edit. Developers get code they can read.', 'sage')) }}</p>

      <p class="btn-row hero-btns">
        <a class="btn" href="{{ esc_url(\App\field_href('home_cta_primary_url', '/contact/')) }}">
          {{ \App\field('home_cta_primary', __('Say hello', 'sage')) }}
        </a>
        <a class="btn btn-outline" href="{{ esc_url(\App\field_href('home_cta_secondary_url', '/projects/')) }}">
          {{ \App\field('home_cta_secondary', __('See example sites', 'sage')) }}
        </a>
      </p>

      @if (! empty($gh['public_repos']) || ! empty($gh['followers']))
        <dl class="stat-row stat-row--clean">
          @if (! empty($gh['public_repos']))
            <div>
              <dt><a href="{{ esc_url($ghUrl.'?tab=repositories') }}" rel="me noopener" target="_blank">{{ number_format_i18n((int) $gh['public_repos']) }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a></dt>
              <dd>{{ __('public repos', 'sage') }}</dd>
            </div>
          @endif
          @if (! empty($gh['followers']))
            <div>
              <dt><a href="{{ esc_url($ghUrl.'?tab=followers') }}" rel="me noopener" target="_blank">{{ number_format_i18n((int) $gh['followers']) }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a></dt>
              <dd>{{ __('GitHub followers', 'sage') }}</dd>
            </div>
          @endif
          @if (! empty($gh['hireable']))
            <div>
              <dt>{{ __('Open', 'sage') }}</dt>
              <dd>{{ __('for hire', 'sage') }}</dd>
            </div>
          @endif
        </dl>
      @endif

      <nav class="hero-quick-clean" aria-label="{{ __('Quick links', 'sage') }}">
        <a href="{{ $writing }}">{{ \App\field('home_link_writing', __('Journal', 'sage')) }}</a>
        <a href="{{ home_url('/code/') }}">{{ \App\field('home_link_code', __('Code', 'sage')) }}</a>
        <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">GitHub<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
        <a href="{{ esc_url($ghBlog) }}" rel="noopener" target="_blank">Ridges &amp; Valleys<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
        <a href="{{ home_url('/about/') }}">{{ \App\field('home_link_about', __('About', 'sage')) }}</a>
      </nav>
    </div>

    @include('partials.profile-photo', [
      'size'  => 280,
      'class' => 'profile-photo profile-photo--hero profile-photo--portfolio',
      'eager' => true,
    ])

  </div>
</section>

{{-- ── STACK ── clean minimal tool chips --}}
<section class="pf-section home-stack-section" aria-labelledby="stack-heading">
  <div class="container wide">
    <header class="sec-head">
      <div>
        <p class="eyebrow">{{ \App\field('home_stack_kicker', __('The stack', 'sage')) }}</p>
        <h2 id="stack-heading" class="display-title is-section">{{ \App\field('home_stack_h2', __('What I work with', 'sage')) }}</h2>
      </div>
    </header>
    <ul class="stack-chips" role="list">
      @foreach ($stack as $tool)
        <li>
          {!! \App\mh_svg_icon($tool) !!}
          <span>{{ $tool }}</span>
        </li>
      @endforeach
    </ul>
  </div>
</section>

{{-- ── WORK ── example sites --}}
<section class="pf-section home-alt-section" aria-labelledby="work-heading">
  <div class="container wide">
    <header class="sec-head">
      <div>
        <p class="eyebrow">{{ \App\field('home_work_kicker', __('Example sites', 'sage')) }}</p>
        <h2 id="work-heading" class="display-title is-section">{{ \App\field('home_work_h2', __('Work from the studio', 'sage')) }}</h2>
        <p class="sec-intro">{!! \App\field_html('home_work_intro', __('Concept sites from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> for Gettysburg shops, tours, and inns.', 'sage')) !!}</p>
      </div>
      <a class="text-link" href="{{ home_url('/projects/') }}">{{ \App\field('home_work_more', __('All example sites', 'sage')) }}</a>
    </header>
    <div class="card-grid home-card-grid">
      @foreach ($work as $p)
        <article class="home-project-card">
          <p class="pf-meta">{{ $p['cat'] }} · {{ $p['place'] }}</p>
          <h3><a href="{{ home_url('/projects/') }}#{{ $p['slug'] }}">{{ $p['title'] }}</a></h3>
          <p>{{ $p['blurb'] }}</p>
          @if (! empty($p['tech']))
            <p class="pill-row">
              @foreach ($p['tech'] as $t)
                <span class="pill">{!! \App\mh_svg_icon($t, 13) !!} {{ $t }}</span>
              @endforeach
            </p>
          @endif
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- ── RIGHT NOW ── casual about section --}}
<section class="pf-section home-now-section" aria-labelledby="now-heading">
  <div class="container wide">
    <div class="home-now-inner">
      <div class="home-now-copy">
        <p class="eyebrow">{{ \App\field('home_now_kicker', __('Right now', 'sage')) }}</p>
        <h2 id="now-heading" class="display-title is-section">{{ \App\field('home_now_h2', __('WordPress, mostly.', 'sage')) }}</h2>
        <p class="lead">{{ \App\field('home_help_p1', __('I build WordPress sites and plugins from Gettysburg, PA. I\'ve done Power Platform work when a team runs on Microsoft 365, but WordPress is what I reach for.', 'sage')) }}</p>
        <p class="btn-row">
          <a class="btn" href="{{ home_url('/now/') }}">{{ \App\field('home_now_link', __('What I\'m doing now', 'sage')) }}</a>
          <a class="btn btn-outline" href="{{ home_url('/services/') }}">{{ \App\field('home_link_services', __('How I can help', 'sage')) }}</a>
        </p>
      </div>
    </div>
  </div>
</section>

{{-- ── WHO ── audience cards --}}
@include('partials.audience', [
  'kicker'  => \App\field('home_who_kicker', __('Who it\'s for', 'sage')),
  'heading' => \App\field('who_h2', __('Pick a starting point', 'sage')),
  'intro'   => \App\field('who_intro', __('Same site. Useful from different angles.', 'sage')),
])

{{-- ── JOURNAL ── recent posts --}}
<section class="pf-section home-alt-section" aria-labelledby="write-heading">
  <div class="container wide">
    <header class="sec-head">
      <div>
        <p class="eyebrow">{{ \App\field('home_write_kicker', __('Journal', 'sage')) }}</p>
        <h2 id="write-heading" class="display-title is-section">{{ \App\field('home_write_h2', __('Recent posts', 'sage')) }}</h2>
        <p class="sec-intro">{{ \App\field('home_write_intro', __('Short posts on WordPress, plugins, and other web apps. Lots of snippets.', 'sage')) }}</p>
      </div>
      <a class="text-link" href="{{ $writing }}">{{ \App\field('home_write_all', __('All posts', 'sage')) }}</a>
    </header>
    <div class="card-grid card-grid--3">
      @forelse ($posts as $post)
        <article class="post-card">
          @if (! empty($post['thumb']))
            <span class="post-shot" aria-hidden="true">
              <img src="{{ esc_url($post['thumb']) }}" alt="" width="960" height="540" loading="lazy" decoding="async">
            </span>
          @else
            <span class="post-shot" aria-hidden="true">
              <span class="post-shot-fallback">{{ wp_trim_words($post['title'], 4, '') }}</span>
            </span>
          @endif
          <div class="post-body">
            <p class="post-meta">{{ $post['date'] }}@if ($post['cat']) · {{ $post['cat'] }}@endif @if (! empty($post['minutes'])) · {{ sprintf(_n('%d min', '%d min', $post['minutes'], 'sage'), $post['minutes']) }}@endif</p>
            <h3 class="post-card-title">{{ $post['title'] }}</h3>
            <p>{{ $post['ex'] }}</p>
            @include('partials.read-more', ['url' => $post['url'], 'name' => $post['title']])
          </div>
        </article>
      @empty
        <p>{{ \App\field('home_write_empty', __('New posts will show up here.', 'sage')) }}</p>
      @endforelse
    </div>
  </div>
</section>

{{-- ── CODE ── repos --}}
<section class="pf-section" aria-labelledby="code-heading">
  <div class="container wide">
    <header class="sec-head">
      <div>
        <p class="eyebrow">{{ \App\field('home_code_kicker', __('GitHub', 'sage')) }}</p>
        <h2 id="code-heading" class="display-title is-section">{{ \App\field('home_code_h2', __('Code to copy', 'sage')) }}</h2>
        <p class="sec-intro">{{ \App\field('home_code_intro', __('Public repos and short snippets. Fork them or just read.', 'sage')) }}</p>
      </div>
      <a class="text-link" href="{{ home_url('/code/') }}">{{ \App\field('home_code_more', __('More repos and snippets', 'sage')) }}</a>
    </header>
    <div class="card-grid">
      @foreach ($repos as $r)
        @include('partials.repo-card', ['r' => $r])
      @endforeach
    </div>
  </div>
</section>

{{-- ── CTA BAND ── --}}
<section class="cta-band" aria-labelledby="help-heading">
  <div class="container wide cta-band-inner">
    <div>
      <p class="eyebrow eyebrow--on-dark">{{ \App\field('home_help_kicker', __('A question is enough', 'sage')) }}</p>
      <h2 id="help-heading" class="display-title is-section">{{ \App\field('home_help_h2', __('Let\'s talk.', 'sage')) }}</h2>
      <p>{!! \App\field_html('home_help_p2', __('Read <a href="/services/">how I can help</a>, or just send a note. A question about a post is just as welcome as a project inquiry.', 'sage')) !!}</p>
    </div>
    <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">{{ \App\field('home_link_hello', __('Say hello', 'sage')) }}</a>
  </div>
</section>
