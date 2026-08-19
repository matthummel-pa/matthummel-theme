@php
  $posts = \App\mh_latest_posts(3);
  $repos = array_slice(\App\mh_code_page_repos(), 0, 3);
  $snips = array_slice(\App\mh_code_page_snips(), 0, 3);
  $work  = array_slice(\App\mh_work_page_items(), 0, 3);
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
@endphp

<section class="hero">
  <div class="container reader hero-intro">
    @include('partials.profile-photo', ['size' => 96, 'class' => 'profile-photo profile-photo--hero', 'eager' => true])
    <div>
      <h1 class="display-title is-hero">{{ \App\field('home_h1', __('Hello. I’m Matt.', 'sage')) }}</h1>
      <p class="lead">{{ \App\field('home_lede', __('I live in Gettysburg, Pennsylvania. This site is a notebook: writing you can read, code you can copy, and example WordPress sites for local shops. Neighbors, new developers, and business owners are all welcome.', 'sage')) }}</p>
      <p>
        <a href="{{ $writing }}">{{ \App\field('home_link_writing', __('Writing', 'sage')) }}</a>
        · <a href="{{ home_url('/code/') }}">{{ \App\field('home_link_code', __('Code and snippets', 'sage')) }}</a>
        · <a href="{{ home_url('/projects/') }}">{{ \App\field('home_link_work', __('Example sites', 'sage')) }}</a>
        · <a href="{{ home_url('/contact/') }}">{{ \App\field('home_link_hello', __('Say hello', 'sage')) }}</a>
      </p>
    </div>
  </div>
</section>

<section class="pf-section" aria-labelledby="write-heading">
  <div class="container reader">
    <h2 id="write-heading" class="display-title is-section">{{ \App\field('home_write_h2', __('Writing', 'sage')) }}</h2>
    <p>{{ \App\field('home_write_intro', __('Notes on WordPress, Power Platform, and shipping as one person. Many posts include snippets you can paste into a theme or a plugin file.', 'sage')) }}</p>
    @forelse ($posts as $post)
      <article class="post-card">
        <h3 class="post-card-title"><a href="{{ $post['url'] }}">{{ $post['title'] }}</a></h3>
        <p>
          {{ $post['date'] }}@if ($post['cat']) · {{ $post['cat'] }}@endif
          — {{ $post['ex'] }}
        </p>
      </article>
    @empty
      <p>{{ \App\field('home_write_empty', __('New posts will show up here. Categories stay as they are.', 'sage')) }}</p>
    @endforelse
    <p><a href="{{ $writing }}">{{ \App\field('home_write_all', __('All posts', 'sage')) }}</a> · <a href="https://dev.to/matthummel" rel="me">DEV.to</a></p>
  </div>
</section>

<section class="pf-section" aria-labelledby="code-heading">
  <div class="container reader">
    <h2 id="code-heading" class="display-title is-section">{{ \App\field('home_code_h2', __('Code to borrow', 'sage')) }}</h2>
    <p>{{ \App\field('home_code_intro', __('Public repos on GitHub, plus short snippets. Fork them, copy them, or ask if a line is unclear.', 'sage')) }}</p>
    @foreach ($repos as $r)
      <article class="pf-card">
        <h3><a href="{{ $r['url'] }}" rel="noopener" target="_blank">{{ $r['name'] }}</a></h3>
        <p>{{ $r['desc'] }}</p>
      </article>
    @endforeach
    @foreach ($snips as $s)
      <article class="snippet-card">
        <h3>{{ $s['title'] }}</h3>
        <p class="note">{{ $s['note'] }}</p>
        <pre class="snippet"><code>{{ $s['code'] }}</code></pre>
      </article>
    @endforeach
    <p><a href="{{ home_url('/code/') }}">{{ \App\field('home_code_more', __('More repos and snippets', 'sage')) }}</a></p>
  </div>
</section>

<section class="pf-section" aria-labelledby="work-heading">
  <div class="container reader">
    <h2 id="work-heading" class="display-title is-section">{{ \App\field('home_work_h2', __('Example sites', 'sage')) }}</h2>
    <p>{!! \App\field_html('home_work_intro', __('Concept work from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> for Gettysburg tours, inns, and shops. Useful if you run a local business and want to see what a clear WordPress site can look like.', 'sage')) !!}</p>
    @foreach ($work as $p)
      <article class="work-card">
        <h3>{{ $p['title'] }}</h3>
        <p>{{ $p['blurb'] }}</p>
      </article>
    @endforeach
    <p><a href="{{ home_url('/projects/') }}">{{ \App\field('home_work_more', __('All example sites', 'sage')) }}</a></p>
  </div>
</section>

<section class="pf-section" aria-labelledby="help-heading">
  <div class="container reader">
    <h2 id="help-heading" class="display-title is-section">{{ \App\field('home_help_h2', __('If you need a hand', 'sage')) }}</h2>
    <p>{{ \App\field('home_help_p1', __('I work full-time on Microsoft Power Platform. In spare hours I still help with WordPress sites, small apps, and cleanup (speed, accessibility, search).', 'sage')) }}</p>
    <p>{!! \App\field_html('home_help_p2', __('Read <a href="/services/">how I can help</a>, or <a href="/contact/">send a note</a>. A question about a post or a snippet is just as welcome as a build request.', 'sage')) !!}</p>
  </div>
</section>
