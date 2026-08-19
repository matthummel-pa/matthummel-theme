@php
  $posts = \App\mh_latest_posts(3);
  $repos = \App\mh_featured_repos();
  $work  = array_slice(\App\mh_studio_projects(), 0, 6);
  $devto = \App\mh_devto_posts(3);
@endphp

<section class="pf-hero">
  <div class="container">
    <p class="pf-kicker">Full-stack · WordPress · Power Platform</p>
    <h1 class="display-title is-hero">I build sites and apps people can actually use.</h1>
    <p class="lead">I’m Matt Hummel, a developer in Gettysburg, PA. I make WordPress sites, Microsoft Power Platform apps, and full-stack tools. I keep the words simple, the pages fast, and the code easy to follow.</p>
    <div class="btn-row">
      <a class="btn" href="{{ home_url('/projects/') }}">See the work</a>
      <a class="btn btn-outline" href="{{ home_url('/contact/') }}">Say hello</a>
    </div>
  </div>
</section>

<section class="pf-section pf-section--alt" aria-labelledby="focus-heading">
  <div class="container">
    <h2 id="focus-heading" class="display-title is-section">What I focus on</h2>
    <p class="lead">Three lanes. Same bar: clear, accessible, and built to last.</p>
    <div class="pf-grid">
      <article class="pf-card">
        <h3>WordPress</h3>
        <p>Custom Sage themes, Gutenberg blocks, and clean handoffs. You own the site. No page-builder bloat.</p>
      </article>
      <article class="pf-card">
        <h3>Power Platform</h3>
        <p>Power Apps, Power Automate, and SharePoint. I turn paper and inbox work into apps teams can trust.</p>
      </article>
      <article class="pf-card">
        <h3>Full-stack</h3>
        <p>Front-end to API. React, PHP, and Node when a CMS is not the right fit. Code you can read on GitHub.</p>
      </article>
    </div>
  </div>
</section>

<section class="pf-section" aria-labelledby="work-heading">
  <div class="container">
    <h2 id="work-heading" class="display-title is-section">Selected work</h2>
    <p class="lead">Concept sites from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> — local WordPress builds for Gettysburg shops, inns, and tours.</p>
    <div class="pf-grid">
      @foreach ($work as $p)
        <article class="pf-card">
          <p class="pf-meta">{{ $p['cat'] }} · {{ $p['place'] }}</p>
          <h3>{{ $p['title'] }}</h3>
          <p>{{ $p['blurb'] }}</p>
          <div class="pill-row">
            @foreach ($p['tech'] as $t)
              <span class="pill">{{ $t }}</span>
            @endforeach
          </div>
        </article>
      @endforeach
    </div>
    <p style="margin-top:1.25rem"><a class="btn btn-outline" href="{{ home_url('/projects/') }}">All Ridges &amp; Valleys work</a></p>
  </div>
</section>

<section class="pf-section pf-section--alt" aria-labelledby="code-heading">
  <div class="container">
    <h2 id="code-heading" class="display-title is-section">Code I share</h2>
    <p class="lead">Open repos from <a href="https://github.com/matthummel-pa" rel="me">github.com/matthummel-pa</a>. Clone them. Read them. Fork them.</p>
    <div class="pf-grid">
      @foreach ($repos as $r)
        <article class="pf-card">
          <h3><a href="{{ $r['url'] }}" rel="noopener" target="_blank">{{ $r['name'] }}</a></h3>
          <p>{{ $r['desc'] }}</p>
          <div class="pill-row">
            @foreach ($r['tags'] as $t)
              <span class="pill">{{ $t }}</span>
            @endforeach
          </div>
        </article>
      @endforeach
    </div>
    <p style="margin-top:1.25rem"><a class="btn btn-outline" href="{{ home_url('/code/') }}">Code and snippets</a></p>
  </div>
</section>

<section class="pf-section" aria-labelledby="write-heading">
  <div class="container">
    <h2 id="write-heading" class="display-title is-section">Writing</h2>
    <p class="lead">Notes on WordPress, Power Platform, and shipping as a solo developer. Posts stay here. I also share on DEV.to.</p>
    <div class="post-list">
      @forelse ($posts as $post)
        <article class="post-card">
          <p class="post-meta">
            {{ $post['date'] }}
            @if ($post['cat'])
              · {{ $post['cat'] }}
            @endif
          </p>
          <h3 class="post-card-title"><a href="{{ $post['url'] }}">{{ $post['title'] }}</a></h3>
          <p>{{ $post['ex'] }}</p>
        </article>
      @empty
        <p>Blog posts will show here. Categories stay as they are.</p>
      @endforelse
    </div>
    <p style="margin-top:1.25rem"><a href="{{ get_permalink(get_option('page_for_posts')) ?: home_url('/blog/') }}">All posts</a>
      · <a href="https://dev.to/matthummel" rel="me">DEV.to</a></p>

    @if ($devto)
      <h3 class="display-title is-section" style="margin-top:2rem;font-size:1.25rem">On DEV.to</h3>
      <ul>
        @foreach ($devto as $d)
          <li><a href="{{ esc_url($d['url']) }}" rel="noopener" target="_blank">{{ $d['title'] }}</a></li>
        @endforeach
      </ul>
    @endif
  </div>
</section>

<section class="pf-section pf-section--alt" aria-labelledby="cta-heading">
  <div class="container">
    <h2 id="cta-heading" class="display-title is-section">Open to a few side projects</h2>
    <p class="lead">I have a full-time Power Platform job. I still take a small number of WordPress, Power Platform, and full-stack jobs. If you have a clear problem, write me.</p>
    <a class="btn" href="{{ home_url('/contact/') }}">Get in touch</a>
  </div>
</section>
