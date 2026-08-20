@php
  $posts = \App\mh_latest_posts(3);
  $gh = \App\Github::fetchUser(\App\mh_github_login());
  $repos = \App\mh_home_github_repos(6);
  $snips = array_slice(\App\mh_code_page_snips(), 0, 2);
  $work  = array_slice(\App\mh_work_page_items(), 0, 6);
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $ghUrl = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $ghBlog = \App\mh_github_blog_url($gh);
  $stack = \App\field_lines('home_stack', [
    __('WordPress', 'sage'),
    __('Plugins', 'sage'),
    __('PHP', 'sage'),
    __('JavaScript', 'sage'),
    __('React', 'sage'),
    __('HTML & CSS', 'sage'),
    __('Git', 'sage'),
    __('Sage / Blade', 'sage'),
    __('Power Apps', 'sage'),
    __('Power Automate', 'sage'),
  ]);
  foreach ($repos as $r) {
      $lang = trim((string) ($r['lang'] ?? ''));
      if ($lang !== '' && ! in_array($lang, $stack, true) && ! ($lang === 'HTML' && in_array('HTML & CSS', $stack, true))) {
          $stack[] = $lang;
      }
  }
@endphp

<section class="hero" aria-labelledby="hero-heading">
  <div class="hero-graphic" aria-hidden="true">
    <span class="hero-blob hero-blob--1"></span>
    <span class="hero-blob hero-blob--2"></span>
    <span class="hero-blob hero-blob--3"></span>
    <span class="hero-blob hero-blob--4"></span>
  </div>
  <div class="container wide hero-inner">
    <div class="hero-copy">
      <p class="eyebrow eyebrow--on-dark">{{ \App\field('home_kicker', $gh['location'] ?: __('Gettysburg, Pennsylvania', 'sage')) }}</p>
      <h1 id="hero-heading" class="display-title is-hero">{{ \App\field('home_h1', $gh['name'] ?: __('Matt Hummel', 'sage')) }}</h1>
      <p class="hero-roles">{{ \App\field('home_role', __('Full-stack developer. WordPress, plugins, and other web apps.', 'sage')) }}</p>
      <p class="lead lead--on-dark">{{ \App\field('home_lede', __('I build WordPress sites, plugins, and other web apps. Shops get a site they can edit. Developers can copy the code. I still do some Power Platform work when it helps.', 'sage')) }}</p>
      <p class="btn-row">
        <a class="btn btn-on-dark" href="{{ home_url('/projects/') }}">{{ \App\field('home_cta_primary', __('See example sites', 'sage')) }}</a>
        <a class="btn btn-ghost" href="{{ home_url('/contact/') }}">{{ \App\field('home_cta_secondary', ! empty($gh['hireable']) ? __('Let’s work together', 'sage') : __('Say hello', 'sage')) }}</a>
        <a class="btn btn-ghost" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">{{ __('GitHub', 'sage') }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
      </p>
      @if (! empty($gh['public_repos']) || ! empty($gh['followers']))
        <dl class="stat-row stat-row--on-dark">
          @if (! empty($gh['public_repos']))
            <div>
              <dt><a href="{{ esc_url($ghUrl.'?tab=repositories') }}" rel="me noopener" target="_blank">{{ number_format_i18n((int) $gh['public_repos']) }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a></dt>
              <dd>{{ __('Public repos', 'sage') }}</dd>
            </div>
          @endif
          @if (! empty($gh['followers']))
            <div>
              <dt><a href="{{ esc_url($ghUrl.'?tab=followers') }}" rel="me noopener" target="_blank">{{ number_format_i18n((int) $gh['followers']) }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a></dt>
              <dd>{{ __('Followers', 'sage') }}</dd>
            </div>
          @endif
          @if (! empty($gh['hireable']))
            <div>
              <dt>{{ __('Open', 'sage') }}</dt>
              <dd>{{ __('Available for hire', 'sage') }}</dd>
            </div>
          @endif
        </dl>
      @endif
      <p class="hero-quick">
        <a href="{{ $writing }}">{{ \App\field('home_link_writing', __('Writing', 'sage')) }}</a>
        <a href="{{ home_url('/code/') }}">{{ \App\field('home_link_code', __('Code and snippets', 'sage')) }}</a>
        <a href="{{ esc_url($ghBlog) }}" rel="noopener" target="_blank">Ridges &amp; Valleys<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
        <a href="{{ home_url('/about/') }}">{{ \App\field('home_link_about', __('About', 'sage')) }}</a>
      </p>
    </div>
    @include('partials.profile-photo', ['size' => 300, 'class' => 'profile-photo profile-photo--hero', 'eager' => true])
  </div>
</section>

<section class="pf-section" aria-labelledby="stack-heading">
  <div class="container wide">
    <header class="sec-head">
      <div>
        <p class="eyebrow">{{ \App\field('home_stack_kicker', __('Tools I ship with', 'sage')) }}</p>
        <h2 id="stack-heading" class="display-title is-section">{{ \App\field('home_stack_h2', __('Stack', 'sage')) }}</h2>
      </div>
    </header>
    <ul class="stack-grid">
      @foreach ($stack as $tool)
        <li>
          {!! \App\mh_svg_icon($tool) !!}
          <span>{{ $tool }}</span>
        </li>
      @endforeach
    </ul>
  </div>
</section>

@include('partials.audience', [
  'kicker' => \App\field('home_who_kicker', __('Who this is for', 'sage')),
  'heading' => \App\field('who_h2', __('Four doors in', 'sage')),
  'intro' => \App\field('who_intro', __('Same site. Different starting points.', 'sage')),
  'alt' => true,
])

<section class="pf-section pf-section--navy" aria-labelledby="now-heading">
  <div class="container wide now-card">
    <p class="eyebrow eyebrow--on-dark">{{ \App\field('home_now_kicker', __('Right now', 'sage')) }}</p>
    <h2 id="now-heading" class="display-title is-section">{{ \App\field('home_now_h2', __('WordPress, plugins, and other web apps.', 'sage')) }}</h2>
    <p class="lead lead--on-dark">{{ \App\field('home_help_p1', __('I build WordPress sites, plugins, and other web apps. I still do some Power Platform work when a team already lives in Microsoft 365.', 'sage')) }}</p>
    <p class="btn-row">
      <a class="btn btn-on-dark" href="{{ home_url('/now/') }}">{{ \App\field('home_now_link', __('What I’m doing now', 'sage')) }}</a>
      <a class="btn btn-ghost" href="{{ home_url('/services/') }}">{{ \App\field('home_link_services', __('How I can help', 'sage')) }}</a>
    </p>
  </div>
</section>

<section class="pf-section" aria-labelledby="work-heading">
  <div class="container wide">
    <header class="sec-head">
      <div>
        <p class="eyebrow">{{ \App\field('home_work_kicker', __('Studio concepts', 'sage')) }}</p>
        <h2 id="work-heading" class="display-title is-section">{{ \App\field('home_work_h2', __('Example sites', 'sage')) }}</h2>
        <p class="sec-intro">{!! \App\field_html('home_work_intro', __('Concept work from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> for Gettysburg tours, inns, and shops.', 'sage')) !!}</p>
      </div>
      <a class="text-link" href="{{ home_url('/projects/') }}">{{ \App\field('home_work_more', __('All example sites', 'sage')) }}</a>
    </header>
    <div class="card-grid">
      @foreach ($work as $p)
        <article class="lift-card">
          <p class="pf-meta">{{ $p['cat'] }} · {{ $p['place'] }}</p>
          <h3>{{ $p['title'] }}</h3>
          <p>{{ $p['blurb'] }}</p>
          @if (! empty($p['tech']))
            <p class="pill-row">
              @foreach ($p['tech'] as $t)
                <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ $t }}</span>
              @endforeach
            </p>
          @endif
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="pf-section pf-section--alt" aria-labelledby="write-heading">
  <div class="container wide">
    <header class="sec-head">
      <div>
        <p class="eyebrow">{{ \App\field('home_write_kicker', __('Notes from the bench', 'sage')) }}</p>
        <h2 id="write-heading" class="display-title is-section">{{ \App\field('home_write_h2', __('Writing', 'sage')) }}</h2>
        <p class="sec-intro">{{ \App\field('home_write_intro', __('Notes on WordPress, plugins, and other web apps. Many posts include snippets you can paste into a theme or a plugin.', 'sage')) }}</p>
      </div>
      <a class="text-link" href="{{ $writing }}">{{ \App\field('home_write_all', __('All posts', 'sage')) }}</a>
    </header>
    <div class="card-grid card-grid--3">
      @forelse ($posts as $post)
        <article class="lift-card">
          <p class="pf-meta">{{ $post['date'] }}@if ($post['cat']) · {{ $post['cat'] }}@endif</p>
          <h3 class="post-card-title"><a href="{{ $post['url'] }}">{{ $post['title'] }}</a></h3>
          <p>{{ $post['ex'] }}</p>
        </article>
      @empty
        <p>{{ \App\field('home_write_empty', __('New posts will show up here. Categories stay as they are.', 'sage')) }}</p>
      @endforelse
    </div>
  </div>
</section>

<section class="pf-section" aria-labelledby="code-heading">
  <div class="container wide">
    <header class="sec-head">
      <div>
        <p class="eyebrow">{{ \App\field('home_code_kicker', __('Public on GitHub', 'sage')) }}</p>
        <h2 id="code-heading" class="display-title is-section">{{ \App\field('home_code_h2', __('Code to borrow', 'sage')) }}</h2>
        <p class="sec-intro">{{ \App\field('home_code_intro', __('Public repos plus short snippets. Fork them, copy them, or ask if a line is unclear.', 'sage')) }}</p>
      </div>
      <a class="text-link" href="{{ home_url('/code/') }}">{{ \App\field('home_code_more', __('More repos and snippets', 'sage')) }}</a>
    </header>
    <div class="card-grid">
      @foreach ($repos as $r)
        <article class="lift-card">
          <h3><a href="{{ $r['url'] }}" rel="noopener" target="_blank">{{ $r['name'] }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a></h3>
          @if (! empty($r['desc']))
            <p>{{ $r['desc'] }}</p>
          @endif
          <p class="pill-row">
            @foreach (($r['tags'] ?? []) as $t)
              <span class="pill">{{ $t }}</span>
            @endforeach
            @if (! empty($r['lang']))
              <span class="pill">{{ $r['lang'] }}</span>
            @endif
            @if (! empty($r['stars']))
              <span class="pill">{{ sprintf(_n('%s star', '%s stars', (int) $r['stars'], 'sage'), number_format_i18n((int) $r['stars'])) }}</span>
            @endif
          </p>
        </article>
      @endforeach
    </div>
    <div class="snippet-grid">
    @foreach ($snips as $s)
      <article class="snippet-card">
        <h3>{{ $s['title'] }}</h3>
        <p class="note">{{ $s['note'] }}</p>
        <pre class="snippet"><code>{{ $s['code'] }}</code></pre>
      </article>
    @endforeach
    </div>
  </div>
</section>

<section class="cta-band" aria-labelledby="help-heading">
  <div class="container wide cta-band-inner">
    <div>
      <p class="eyebrow eyebrow--on-dark">{{ \App\field('home_help_kicker', __('A question is enough', 'sage')) }}</p>
      <h2 id="help-heading" class="display-title is-section">{{ \App\field('home_help_h2', __('If you need a hand', 'sage')) }}</h2>
      <p>{!! \App\field_html('home_help_p2', __('Read <a href="/services/">how I can help</a>, or send a note. A question about a post or a snippet is just as welcome as a build request.', 'sage')) !!}</p>
    </div>
    <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">{{ \App\field('home_link_hello', __('Say hello', 'sage')) }}</a>
  </div>
</section>
