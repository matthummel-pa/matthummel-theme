@php
  $posts = \App\mh_latest_posts(3);
  $repos = \App\mh_featured_repos();
  $work  = array_slice(\App\mh_studio_projects(), 0, 3);
@endphp

<section class="hero">
  <div class="container reader">
    <h1 class="display-title is-hero">I build sites and apps people can actually use.</h1>
    <p class="lead">Matt Hummel. Gettysburg, PA. Sage themes, Microsoft Power Platform, and small full-stack tools. Clear words. Fast pages. Code you can own.</p>
    <div class="btn-row">
      <a class="btn" href="{{ home_url('/contact/') }}">Start a project</a>
      <a class="btn btn-outline" href="{{ home_url('/projects/') }}">See the work</a>
    </div>
  </div>
</section>

<section class="pf-section" aria-labelledby="partner-heading">
  <div class="container reader">
    <h2 id="partner-heading" class="display-title is-section">A builder you can hand the keys to.</h2>
    <p>Most sites fail because they are heavy, vague, or locked to a page builder. I keep the stack small. You own the hosting. You own the code.</p>
    <p>I work full-time on Power Platform. I take a few WordPress and full-stack jobs on the side. If it is not a fit, I say so. <a href="{{ home_url('/about/') }}">More about me</a>.</p>
  </div>
</section>

<section class="pf-section" aria-labelledby="help-heading">
  <div class="container reader">
    <h2 id="help-heading" class="display-title is-section">How I can help</h2>
    <h3>WordPress</h3>
    <p>Sage 11 themes, Gutenberg, and clean handoffs. Fast, accessible, no builder bloat.</p>
    <h3>Power Platform</h3>
    <p>Power Apps, Automate, and SharePoint. Turn inbox work into apps teams can run.</p>
    <h3>Full-stack</h3>
    <p>React, PHP, and APIs when a CMS is the wrong tool. Public code on GitHub.</p>
    <h3>Fixes that matter</h3>
    <p>Speed, accessibility, and SEO cleanup. A punch list, not a mystery rewrite. <a href="{{ home_url('/services/') }}">All services</a>.</p>
  </div>
</section>

<section class="pf-section" aria-labelledby="work-heading">
  <div class="container reader">
    <h2 id="work-heading" class="display-title is-section">Selected work</h2>
    <p>Studio concepts from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> for Gettysburg shops, inns, and tours.</p>
    @foreach ($work as $p)
      <article class="work-card">
        <h3>{{ $p['title'] }}</h3>
        <p>{{ $p['blurb'] }} {{ $p['cat'] }}, {{ $p['place'] }}.</p>
      </article>
    @endforeach
    <p><a href="{{ home_url('/projects/') }}">View all work</a></p>
  </div>
</section>

<section class="pf-section" aria-labelledby="code-heading">
  <div class="container reader">
    <h2 id="code-heading" class="display-title is-section">Repos I keep public</h2>
    @foreach ($repos as $r)
      <article class="pf-card">
        <h3><a href="{{ $r['url'] }}" rel="noopener" target="_blank">{{ $r['name'] }}</a></h3>
        <p>{{ $r['desc'] }} {{ implode(', ', $r['tags']) }}.</p>
      </article>
    @endforeach
    <p><a href="{{ home_url('/code/') }}">Code and snippets</a></p>
  </div>
</section>

<section class="pf-section" aria-labelledby="write-heading">
  <div class="container reader">
    <h2 id="write-heading" class="display-title is-section">Notes I can stand behind</h2>
    @forelse ($posts as $post)
      <article class="post-card">
        <h3 class="post-card-title"><a href="{{ $post['url'] }}">{{ $post['title'] }}</a></h3>
        <p>
          {{ $post['date'] }}@if ($post['cat']) · {{ $post['cat'] }}@endif
          — {{ $post['ex'] }}
        </p>
      </article>
    @empty
      <p>Blog posts will show here. Categories stay as they are.</p>
    @endforelse
    <p><a href="{{ get_permalink(get_option('page_for_posts')) ?: home_url('/blog/') }}">All posts</a></p>
  </div>
</section>

<section class="pf-section" aria-labelledby="cta-heading">
  <div class="container reader">
    <h2 id="cta-heading" class="display-title is-section">Have a clear problem?</h2>
    <p>I take a few side projects. Tell me what you need in plain words. <a href="{{ home_url('/contact/') }}">Get in touch</a>.</p>
  </div>
</section>
