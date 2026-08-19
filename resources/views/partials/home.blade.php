@php
  $posts = \App\mh_latest_posts(3);
  $repos = \App\mh_featured_repos();
  $snips = array_slice(\App\mh_code_snippets(), 0, 2);
  $work  = array_slice(\App\mh_studio_projects(), 0, 3);
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
@endphp

<section class="hero">
  <div class="container reader">
    <h1 class="display-title is-hero">Hello. I’m Matt.</h1>
    <p class="lead">I live in Gettysburg, PA. This site is a notebook: posts you can read, code you can copy, and example WordPress sites for local shops. New developers, neighbors, and business owners are all welcome.</p>
    <p>
      <a href="{{ $writing }}">Writing</a>
      · <a href="{{ home_url('/code/') }}">Code and snippets</a>
      · <a href="{{ home_url('/projects/') }}">Example sites</a>
      · <a href="{{ home_url('/contact/') }}">Say hello</a>
    </p>
  </div>
</section>

<section class="pf-section" aria-labelledby="write-heading">
  <div class="container reader">
    <h2 id="write-heading" class="display-title is-section">Writing</h2>
    <p>Tutorials and notes on WordPress, Power Platform, and shipping as one person. Many posts include snippets you can paste into a theme or a function file.</p>
    @forelse ($posts as $post)
      <article class="post-card">
        <h3 class="post-card-title"><a href="{{ $post['url'] }}">{{ $post['title'] }}</a></h3>
        <p>
          {{ $post['date'] }}@if ($post['cat']) · {{ $post['cat'] }}@endif
          — {{ $post['ex'] }}
        </p>
      </article>
    @empty
      <p>New posts will show up here. Categories stay as they are.</p>
    @endforelse
    <p><a href="{{ $writing }}">All posts</a> · <a href="https://dev.to/matthummel" rel="me">DEV.to</a></p>
  </div>
</section>

<section class="pf-section" aria-labelledby="code-heading">
  <div class="container reader">
    <h2 id="code-heading" class="display-title is-section">Code to borrow</h2>
    <p>Public repos on GitHub, plus short snippets. Fork them, copy them, or ask a question if a line is unclear.</p>
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
    <p><a href="{{ home_url('/code/') }}">More repos and snippets</a></p>
  </div>
</section>

<section class="pf-section" aria-labelledby="work-heading">
  <div class="container reader">
    <h2 id="work-heading" class="display-title is-section">Example sites</h2>
    <p>Concept work from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> for Gettysburg tours, inns, and shops. Useful if you run a local business and want to see what a clear WordPress site can look like.</p>
    @foreach ($work as $p)
      <article class="work-card">
        <h3>{{ $p['title'] }}</h3>
        <p>{{ $p['blurb'] }}</p>
      </article>
    @endforeach
    <p><a href="{{ home_url('/projects/') }}">All example sites</a></p>
  </div>
</section>

<section class="pf-section" aria-labelledby="help-heading">
  <div class="container reader">
    <h2 id="help-heading" class="display-title is-section">If you need a hand</h2>
    <p>I work full-time on Microsoft Power Platform. In spare hours I still help with WordPress sites, small apps, and cleanup (speed, accessibility, SEO).</p>
    <p>Read <a href="{{ home_url('/services/') }}">how I can help</a>, or <a href="{{ home_url('/contact/') }}">send a note</a>. A question about a post or a snippet is just as welcome as a build request.</p>
  </div>
</section>
