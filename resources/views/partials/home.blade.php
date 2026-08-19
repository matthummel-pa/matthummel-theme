@php
  $posts = \App\mh_latest_posts(3);
  $repos = \App\mh_featured_repos();
  $work  = array_slice(\App\mh_studio_projects(), 0, 4);
@endphp

<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <p class="eyebrow">WordPress · Power Platform · Full-stack</p>
      <h1 class="display-title is-hero">I build sites and apps people can actually use.</h1>
      <p class="lead">Matt Hummel. Gettysburg, PA. Sage themes, Microsoft Power Platform, and small full-stack tools. Clear words. Fast pages. Code you can own.</p>
      <div class="btn-row">
        <a class="btn" href="{{ home_url('/contact/') }}">Start a project</a>
        <a class="btn btn-outline" href="{{ home_url('/projects/') }}">See the work</a>
      </div>
    </div>
    <aside class="hero-panel" aria-label="{{ __('Snapshot', 'matthummel') }}">
      <div class="hero-orb" aria-hidden="true"></div>
      <dl class="stat-stack">
        <div>
          <dt>15+</dt>
          <dd>Years on the web</dd>
        </div>
        <div>
          <dt>3</dt>
          <dd>Lanes I ship in</dd>
        </div>
        <div>
          <dt>PA</dt>
          <dd>Based in Gettysburg</dd>
        </div>
      </dl>
    </aside>
  </div>
</section>

<section class="pf-section" aria-labelledby="partner-heading">
  <div class="container split">
    <div>
      <p class="eyebrow">About</p>
      <h2 id="partner-heading" class="display-title is-section">A builder you can hand the keys to.</h2>
    </div>
    <div>
      <p class="lead">Most sites fail because they are heavy, vague, or locked to a page builder. I keep the stack small. You own the hosting. You own the code.</p>
      <p>I work full-time on Power Platform. I take a few WordPress and full-stack jobs on the side. If it is not a fit, I say so.</p>
      <a class="text-link" href="{{ home_url('/about/') }}">More about me</a>
    </div>
  </div>
</section>

<section class="pf-section pf-section--alt" aria-labelledby="help-heading">
  <div class="container">
    <div class="sec-head">
      <div>
        <p class="eyebrow">Services</p>
        <h2 id="help-heading" class="display-title is-section">How I can help</h2>
      </div>
      <a class="text-link" href="{{ home_url('/services/') }}">All services</a>
    </div>
    <ol class="svc-list">
      <li class="svc-item">
        <span class="svc-num" aria-hidden="true">01</span>
        <h3>WordPress</h3>
        <p>Sage 11 themes, Gutenberg, and clean handoffs. Fast, accessible, no builder bloat.</p>
      </li>
      <li class="svc-item">
        <span class="svc-num" aria-hidden="true">02</span>
        <h3>Power Platform</h3>
        <p>Power Apps, Automate, and SharePoint. Turn inbox work into apps teams can run.</p>
      </li>
      <li class="svc-item">
        <span class="svc-num" aria-hidden="true">03</span>
        <h3>Full-stack</h3>
        <p>React, PHP, and APIs when a CMS is the wrong tool. Public code on GitHub.</p>
      </li>
      <li class="svc-item">
        <span class="svc-num" aria-hidden="true">04</span>
        <h3>Fixes that matter</h3>
        <p>Speed, accessibility, and SEO cleanup. A punch list, not a mystery rewrite.</p>
      </li>
    </ol>
  </div>
</section>

<section class="pf-section" aria-labelledby="work-heading">
  <div class="container">
    <div class="sec-head">
      <div>
        <p class="eyebrow">Work</p>
        <h2 id="work-heading" class="display-title is-section">Selected work</h2>
        <p class="lead">Studio concepts from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> for Gettysburg shops, inns, and tours.</p>
      </div>
      <a class="text-link" href="{{ home_url('/projects/') }}">View all work</a>
    </div>
    <div class="work-grid">
      @foreach ($work as $i => $p)
        <article class="work-card">
          <p class="pf-meta">{{ $p['cat'] }} · {{ $p['place'] }}</p>
          <h3>{{ $p['title'] }}</h3>
          <p>{{ $p['blurb'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="pf-section pf-section--alt" aria-labelledby="process-heading">
  <div class="container">
    <div class="sec-head">
      <div>
        <p class="eyebrow">Process</p>
        <h2 id="process-heading" class="display-title is-section">How a job usually goes</h2>
      </div>
    </div>
    <ol class="process-list">
      <li>
        <h3>Listen</h3>
        <p>A short note or call. What is broken, who it is for, and what “done” means.</p>
      </li>
      <li>
        <h3>Build</h3>
        <p>Sage, Power Platform, or a small app. You see progress. No black box.</p>
      </li>
      <li>
        <h3>Hand off</h3>
        <p>You own the repo, the host, and the logins. I leave notes you can follow.</p>
      </li>
    </ol>
  </div>
</section>

<section class="pf-section" aria-labelledby="code-heading">
  <div class="container">
    <div class="sec-head">
      <div>
        <p class="eyebrow">Code</p>
        <h2 id="code-heading" class="display-title is-section">Repos I keep public</h2>
      </div>
      <a class="text-link" href="{{ home_url('/code/') }}">Code and snippets</a>
    </div>
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
  </div>
</section>

<section class="pf-section pf-section--alt" aria-labelledby="write-heading">
  <div class="container">
    <div class="sec-head">
      <div>
        <p class="eyebrow">Writing</p>
        <h2 id="write-heading" class="display-title is-section">Notes I can stand behind</h2>
      </div>
      <a class="text-link" href="{{ get_permalink(get_option('page_for_posts')) ?: home_url('/blog/') }}">All posts</a>
    </div>
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
  </div>
</section>

<section class="pf-section" aria-labelledby="faq-heading">
  <div class="container faq-wrap">
    <p class="eyebrow">FAQ</p>
    <h2 id="faq-heading" class="display-title is-section">Quick answers</h2>
    <div class="faq-list">
      <details>
        <summary>What kinds of jobs do you take?</summary>
        <p>WordPress (especially Sage), Power Platform apps, and small full-stack tools. A few at a time. Not ads or social retainers.</p>
      </details>
      <details>
        <summary>How fast do you reply?</summary>
        <p>Usually one or two business days. Write a short note with the problem and the deadline.</p>
      </details>
      <details>
        <summary>Do I own the site when we are done?</summary>
        <p>Yes. Domain, hosting, and code. I do not lock you into a builder you cannot leave.</p>
      </details>
      <details>
        <summary>Where do you work from?</summary>
        <p>Gettysburg, Pennsylvania. Remote is fine. Local Adams County work also lives at Ridges &amp; Valleys.</p>
      </details>
    </div>
  </div>
</section>

<section class="cta-band" aria-labelledby="cta-heading">
  <div class="container cta-band-inner">
    <div>
      <p class="eyebrow eyebrow--on-dark">Contact</p>
      <h2 id="cta-heading" class="display-title is-section">Have a clear problem?</h2>
      <p>I take a few side projects. Tell me what you need in plain words.</p>
    </div>
    <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">Get in touch</a>
  </div>
</section>
