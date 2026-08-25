{{--
  Template Name: About
--}}
@extends('layouts.app')

@php
  $gh           = \App\Github::fetchUser(\App\mh_github_login());
  $ghUrl        = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $ghBlog       = \App\mh_github_blog_url($gh);
  $writing      = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $latestPosts  = \App\mh_latest_posts(3);
  $yearsBuilding = (int) date('Y') - 2009;

  $services = [
    [
      'icon'  => 'wordpress',
      'title' => 'WordPress sites',
      'body'  => 'New sites from scratch, existing sites that need work, and themes built so the owner can edit their own pages. Mostly Sage and Blade, plain PHP, and front-end work that holds up.',
    ],
    [
      'icon'  => 'plugins',
      'title' => 'Plugins and tools',
      'body'  => 'Custom PHP when WordPress needs a part it doesn\'t have. Single-purpose, well-documented, and written so any developer can read it later.',
    ],
    [
      'icon'  => 'code',
      'title' => 'Other web apps',
      'body'  => 'React front-ends, APIs, and anything that doesn\'t belong in a theme. Power Platform when a team already lives in Microsoft 365 and it\'s the right fit.',
    ],
  ];

  $workTypes = [
    ['Full-time roles', 'WordPress, PHP, and web development. Open to on-site, hybrid, or remote.'],
    ['Contract and freelance', 'Project-based work with a clear, written scope.'],
    ['Agency sub-contracting', 'You keep the client relationship. I build the thing.'],
    ['Part-time arrangements', 'A few hours a week or a focused sprint. Flexible.'],
  ];
@endphp

@section('content')

{{-- ══════════════════════════════════════════════════════════
     HERO
     ══════════════════════════════════════════════════════════ --}}
@component('partials.page-hero', ['innerClass' => 'hero-intro'])
  <div>
    <p class="eyebrow">About</p>
    <h1 class="display-title is-hero">
      {{ \App\field('about_h1', __('A little background.', 'sage')) }}
    </h1>
    <p class="lead">
      {{ \App\field('about_lede', __('I work in PHP and Blade, write front-end in Tailwind, and deploy with GitHub Actions. I lean toward clean, maintainable code over clever code — because the person after me needs to read it too. Based in Gettysburg, PA.', 'sage')) }}
    </p>
    <p class="about-hero-links">
      <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
        {!! \App\mh_svg_icon('github', 15) !!} GitHub
      </a>
      <a href="{{ $writing }}">{!! \App\mh_svg_icon('pen', 15) !!} Journal</a>
      <a href="{{ esc_url($ghBlog) }}" rel="noopener" target="_blank">{!! \App\mh_svg_icon('globe', 15) !!} Ridges &amp; Valleys</a>
    </p>
  </div>
  @include('partials.profile-photo', [
    'size'  => 280,
    'class' => 'profile-photo profile-photo--hero',
    'eager' => true,
  ])
@endcomponent

{{-- ══════════════════════════════════════════════════════════
     STATS BAND
     ══════════════════════════════════════════════════════════ --}}
<div class="about-stats" role="region" aria-label="Quick facts">
  <div class="container wide about-stats__inner">
    <dl class="about-stats__grid">
      <div class="about-stat">
        <dt class="about-stat__value">{{ $yearsBuilding }}+</dt>
        <dd class="about-stat__label">years building for the web</dd>
      </div>
      @if (! empty($gh['public_repos']))
        <div class="about-stat">
          <dt class="about-stat__value">
            <a href="{{ esc_url($ghUrl.'?tab=repositories') }}" rel="me noopener" target="_blank">
              {{ number_format_i18n($gh['public_repos']) }}
            </a>
          </dt>
          <dd class="about-stat__label">public repositories</dd>
        </div>
      @endif
      <div class="about-stat">
        <dt class="about-stat__value">WordPress</dt>
        <dd class="about-stat__label">primary stack</dd>
      </div>
      <div class="about-stat">
        <dt class="about-stat__value">EST</dt>
        <dd class="about-stat__label">Gettysburg, PA</dd>
      </div>
    </dl>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     STORY
     ══════════════════════════════════════════════════════════ --}}
<section class="pf-section" aria-labelledby="about-story-heading">
  <div class="container wide about-story">

    <div class="about-story__copy">
      <p class="eyebrow">Background</p>
      <h2 id="about-story-heading" class="display-title is-section">
        {{ \App\field('about_story_h2', __('How I got here.', 'sage')) }}
      </h2>

      <p>{{ \App\field('about_p1', __('I started in web doing marketing for higher-education. Building landing pages, updating content, and figuring out why something that looked right wasn\'t getting clicks. That work taught me more about what people actually need than any framework ever did.', 'sage')) }}</p>

      <p>{{ \App\field('about_p2', __('WordPress is the tool I kept coming back to after all of that. Not because it\'s the most exciting option, but because it\'s the most practical one for most businesses. A shop owner can update their own hours, add a product, or fix a typo without waiting on a developer. That matters to me.', 'sage')) }}</p>

      <p>{{ \App\field('about_p3', __('I started Ridges & Valleys as a studio focused on WordPress sites for Gettysburg shops, tours, and inns. It\'s a growing body of work for Adams County businesses. Alongside that, I\'m actively open for new work — full-time, contract, or project-based.', 'sage')) }}</p>

      <p>{{ \App\field('about_p4', __('Most of my public code is on GitHub. Snippets go on the journal. If something helped you, you don\'t need to ask permission to use it.', 'sage')) }}</p>

      <div class="about-story__links">
        <a class="btn" href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 16) !!} Say hello</a>
        <a class="about-text-link" href="{{ home_url('/now/') }}">What I\'m doing now →</a>
      </div>
    </div>

    <aside class="about-story__aside" aria-label="GitHub and elsewhere">
      {{-- GitHub card --}}
      <div class="about-aside-card">
        <div class="about-aside-card__head">
          {!! \App\mh_svg_icon('github', 20) !!}
          <div>
            <p class="about-aside-card__name">
              <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">@matthummel-pa</a>
            </p>
            @if (! empty($gh['created']))
              <p class="about-aside-card__meta">On GitHub since {{ $gh['created'] }}</p>
            @endif
          </div>
        </div>
        @if (! empty($gh['bio']))
          <p class="about-aside-card__bio">{{ $gh['bio'] }}</p>
        @endif
        <ul class="about-aside-card__stats">
          @if (! empty($gh['public_repos']))
            <li>
              <strong>{{ number_format_i18n($gh['public_repos']) }}</strong>
              <span>public repos</span>
            </li>
          @endif
          @if (! empty($gh['followers']))
            <li>
              <strong>{{ number_format_i18n($gh['followers']) }}</strong>
              <span>followers</span>
            </li>
          @endif
        </ul>
        @if (! empty($gh['hireable']))
          <p class="about-aside-avail">
            <span class="h-badge__dot" aria-hidden="true"></span>
            Available for hire
          </p>
        @endif
        <a class="about-aside-card__link" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
          View GitHub profile →
        </a>
      </div>

      {{-- Ridges & Valleys --}}
      <div class="about-aside-card about-aside-card--studio">
        <p class="about-aside-kicker">{!! \App\mh_svg_icon('globe', 16) !!} Current studio</p>
        <h3 class="about-aside-card__title">Ridges &amp; Valleys</h3>
        <p class="about-aside-card__bio">A WordPress studio for Gettysburg shops, tours, and inns in Adams County, PA. Concept sites and real builds for local businesses.</p>
        <a class="about-aside-card__link" href="{{ esc_url($ghBlog) }}" rel="noopener" target="_blank">
          Visit ridgesandvalleys.com →
        </a>
      </div>
    </aside>

  </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     WHAT I BUILD
     ══════════════════════════════════════════════════════════ --}}
<section class="pf-section pf-section--alt" aria-labelledby="about-services-heading">
  <div class="container wide">
    <p class="eyebrow">Services</p>
    <h2 id="about-services-heading" class="display-title is-section">
      {{ \App\field('about_services_h2', __('What I build.', 'sage')) }}
    </h2>
    <p class="sec-intro" style="margin-bottom:2rem">
      {{ \App\field('about_services_intro', __('Most projects are WordPress — sites, plugins, or both. Here\'s the breakdown.', 'sage')) }}
    </p>
    <div class="about-services">
      @foreach ($services as $svc)
        <article class="about-svc-card">
          <div class="about-svc-card__icon">{!! \App\mh_svg_icon($svc['icon'], 24) !!}</div>
          <h3 class="about-svc-card__title">{{ $svc['title'] }}</h3>
          <p class="about-svc-card__body">{{ $svc['body'] }}</p>
        </article>
      @endforeach
    </div>
    <p class="about-services-note">
      {!! \App\field_html('about_services_note', __('Questions about a specific project type? <a href="/contact/">Write a note</a>.', 'sage')) !!}
    </p>
  </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     OPEN FOR WORK
     ══════════════════════════════════════════════════════════ --}}
<section class="pf-section" aria-labelledby="about-work-heading">
  <div class="container wide about-openwork">

    <div class="about-openwork__copy">
      <p class="eyebrow">Availability</p>
      <h2 id="about-work-heading" class="display-title is-section">
        {{ \App\field('about_work_h2', __('Open for work.', 'sage')) }}
      </h2>
      <p>{{ \App\field('about_work_p1', __('I\'m actively looking for new work alongside the studio. That includes full-time roles, contract arrangements, and freelance projects. I\'m based in Gettysburg, PA, and open to remote.', 'sage')) }}</p>
      <p>{{ \App\field('about_work_p2', __('If you\'re a recruiter, a design or marketing agency, or a business that needs a WordPress developer, I\'m glad to hear from you. The best way to start is a short note about what you\'re working on.', 'sage')) }}</p>
      <a class="btn" href="{{ home_url('/contact/') }}" style="margin-top:.5rem">
        {!! \App\mh_svg_icon('mail', 16) !!} Start a conversation
      </a>
    </div>

    <div class="about-openwork__types">
      @foreach ($workTypes as [$type, $detail])
        <div class="about-work-type">
          <span class="about-work-type__check" aria-hidden="true">✓</span>
          <div>
            <p class="about-work-type__title">{{ $type }}</p>
            <p class="about-work-type__detail">{{ $detail }}</p>
          </div>
        </div>
      @endforeach
    </div>

  </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     HOW I WORK
     ══════════════════════════════════════════════════════════ --}}
<section class="pf-section pf-section--alt" aria-labelledby="about-approach-heading">
  <div class="container wide">
    <p class="eyebrow">Approach</p>
    <h2 id="about-approach-heading" class="display-title is-section">
      {{ \App\field('about_values_h2', __('How I work.', 'sage')) }}
    </h2>
    <div class="about-approach">
      <div class="about-approach__item">
        <span class="about-approach__icon">{!! \App\mh_svg_icon('briefcase', 20) !!}</span>
        <div>
          <h3>Clients own everything</h3>
          <p>Hosting, domain, database, and code belong to the client before the project closes. No access after handoff unless you invite me back.</p>
        </div>
      </div>
      <div class="about-approach__item">
        <span class="about-approach__icon">{!! \App\mh_svg_icon('users', 20) !!}</span>
        <div>
          <h3>The admin experience is part of the build</h3>
          <p>A site that's hard to update doesn't get updated. I write edit flows so the owner can change a page in under two minutes without asking me.</p>
        </div>
      </div>
      <div class="about-approach__item">
        <span class="about-approach__icon">{!! \App\mh_svg_icon('code', 20) !!}</span>
        <div>
          <h3>Plain, readable code</h3>
          <p>If a developer can't understand a function in 30 seconds, it's too clever. I write for the next person who has to read it.</p>
        </div>
      </div>
      <div class="about-approach__item">
        <span class="about-approach__icon">{!! \App\mh_svg_icon('cursor-ai', 20) !!}</span>
        <div>
          <h3>AI assists. I review everything.</h3>
          <p>I use Cursor, Claude, and ChatGPT to move faster on repetitive work. Every line ships only after I've read and tested it myself.</p>
        </div>
      </div>
      <div class="about-approach__item">
        <span class="about-approach__icon">{!! \App\mh_svg_icon('book-open', 20) !!}</span>
        <div>
          <h3>Accessibility and plain language by default</h3>
          <p>Keyboard-accessible, screen-reader-friendly pages, written at a grade 7–8 reading level. Not a checkbox — just how the work should be done.</p>
        </div>
      </div>
      <div class="about-approach__item">
        <span class="about-approach__icon">{!! \App\mh_svg_icon('plugins', 20) !!}</span>
        <div>
          <h3>Small, focused plugins</h3>
          <p>One plugin should do one thing well. I audit and remove anything that adds weight without adding clear value. Most sites need 6–8 plugins, not 30.</p>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     FROM THE JOURNAL
     ══════════════════════════════════════════════════════════ --}}
@if (! empty($latestPosts))
<section class="pf-section" aria-labelledby="about-writing-heading">
  <div class="container wide">
    <div class="sec-head">
      <div>
        <p class="eyebrow">Journal</p>
        <h2 id="about-writing-heading" class="display-title is-section">Recent posts.</h2>
      </div>
      <a class="text-link" href="{{ $writing }}">All posts →</a>
    </div>
    <div class="about-posts">
      @foreach ($latestPosts as $post)
        <article class="about-post-row">
          <div class="about-post-row__meta">
            @if ($post['cat'])
              <span class="about-post-cat">{{ $post['cat'] }}</span>
            @endif
            <time datetime="{{ esc_attr($post['date_iso'] ?? '') }}" class="about-post-date">{{ $post['date'] }}</time>
            @if (! empty($post['minutes']))
              <span class="about-post-min">{{ $post['minutes'] }} min</span>
            @endif
          </div>
          <div class="about-post-row__body">
            <h3 class="about-post-row__title">
              <a href="{{ esc_url($post['url']) }}">{{ $post['title'] }}</a>
            </h3>
            <p class="about-post-row__ex">{{ $post['ex'] }}</p>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ══════════════════════════════════════════════════════════
     ELSEWHERE
     ══════════════════════════════════════════════════════════ --}}
<section class="pf-section pf-section--alt" aria-labelledby="about-elsewhere-heading">
  <div class="container wide about-elsewhere">
    <div>
      <p class="eyebrow">Online</p>
      <h2 id="about-elsewhere-heading" class="display-title is-section">Where to find me.</h2>
      <p class="sec-intro">I post most of my code and writing here and on GitHub. The RSS feed is the most reliable way to follow along.</p>
    </div>
    <div class="about-elsewhere__links">
      @include('partials.social', ['labeled' => true])
    </div>
  </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     CTA
     ══════════════════════════════════════════════════════════ --}}
<section class="cta-band" aria-labelledby="about-cta-heading">
  <div class="container wide cta-band-inner">
    <div>
      <p class="eyebrow eyebrow--on-dark">Get in touch</p>
      <h2 id="about-cta-heading" class="display-title is-section">Say hello.</h2>
      <p>A question about a post, a project inquiry, or a job opportunity — all welcome. I usually reply within a day.</p>
    </div>
    <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!} Write a note
    </a>
  </div>
</section>

@endsection
