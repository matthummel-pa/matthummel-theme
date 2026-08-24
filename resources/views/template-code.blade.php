{{--
  Template Name: Code
--}}
@extends('layouts.app')

@section('content')
@php
  $profile  = \App\mh_github_profile();
  $calendar = \App\mh_github_calendar();
  $events   = \App\mh_github_events(8);
  $repos    = \App\mh_code_page_repos();
  $live     = \App\mh_github_live_repos(6);
  $practice = \App\mh_code_page_practice();
  $jobs     = \App\mh_code_page_resume();
  $skills   = \App\mh_code_page_skills();
  $docs     = \App\mh_code_page_resources();
  $login    = \App\mh_github_login();
  $ghUrl    = $profile['url'] ?: 'https://github.com/'.$login;
  $weeks    = $calendar['weeks'] ?? [];
  $total    = (int) ($calendar['total'] ?? 0);
  $linkedin = \App\mh_portfolio_social_defaults()['linkedin'] ?? '';
@endphp

{{-- HERO --}}
@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('code_kicker', __('Code', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('code_h1', __('PHP, WordPress, and open-source code.', 'sage')) }}
  </h1>
  <p class="lead">
    {!! \App\field_html('code_lede', __('I build and maintain WordPress sites and plugins from Gettysburg, PA. Most of my public work is on GitHub — repos you can fork, snippets you can paste, and code written so any developer can read it without asking me first.', 'sage')) !!}
  </p>
  <p class="about-hero-links" style="margin-top:1rem">
    <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
      {!! \App\mh_svg_icon('github', 15) !!} GitHub
    </a>
    <a href="#resume">{!! \App\mh_svg_icon('briefcase', 15) !!} Resume</a>
    <a href="#skills">{!! \App\mh_svg_icon('code', 15) !!} Skills</a>
    @if ($linkedin)
      <a href="{{ esc_url($linkedin) }}" rel="noopener" target="_blank">
        {!! \App\mh_svg_icon('linkedin', 15) !!} LinkedIn
      </a>
    @endif
    <a href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 15) !!} Say hello</a>
  </p>
@endcomponent

{{-- PRACTICE --}}
<section class="pf-section" aria-labelledby="code-practice-heading">
  <div class="container wide">
    <div class="code-section-head">
      <div>
        <p class="eyebrow">Day to day</p>
        <h2 id="code-practice-heading" class="display-title is-section">
          {{ \App\field('code_do_h2', __('What I work on.', 'sage')) }}
        </h2>
        <p class="sec-intro">
          {{ \App\field('code_do_intro', __('WordPress is the main focus. Most projects are Sage, PHP, and front-end work that clients can keep editing after I hand off. I also write React apps and do Power Platform work when a team lives in Microsoft 365.', 'sage')) }}
        </p>
      </div>
      <div class="code-section-links">
        <a class="about-text-link" href="{{ home_url('/services/') }}">Services page →</a>
        <a class="about-text-link" href="{{ home_url('/projects/') }}">Example sites →</a>
      </div>
    </div>
    <ul class="practice-list">
      @foreach ($practice as $item)
        <li>{{ $item }}</li>
      @endforeach
    </ul>
  </div>
</section>

{{-- GITHUB --}}
<section class="pf-section pf-section--alt" id="github" aria-labelledby="code-gh-heading">
  <div class="container wide">
    <p class="eyebrow">Open source</p>
    <h2 id="code-gh-heading" class="display-title is-section">
      {{ \App\field('code_gh_h2', __('GitHub.', 'sage')) }}
    </h2>

    {{-- Profile --}}
    @if (! empty($profile['login']))
    <div class="gh-profile">
      @if (! empty($profile['avatar']))
        <img class="gh-avatar" src="{{ esc_url($profile['avatar']) }}" width="88" height="88" alt="Matt Hummel GitHub avatar" loading="lazy" decoding="async">
      @endif
      <div class="gh-profile-copy">
        <p class="gh-name">{{ $profile['name'] ?: $profile['login'] }}</p>
        <p class="gh-login">
          <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">{{ '@'.$login }}<span class="visually-hidden"> (opens in a new window)</span></a>
          @if (! empty($profile['location']))
            <span class="gh-loc">· {{ $profile['location'] }}</span>
          @endif
          @if (! empty($profile['created']))
            <span class="gh-loc">· Since {{ $profile['created'] }}</span>
          @endif
        </p>
        @if (! empty($profile['bio']))
          <p class="gh-bio">{{ $profile['bio'] }}</p>
        @endif
      </div>
      <dl class="stat-row gh-stats">
        <div>
          <dt>{{ number_format_i18n((int) ($profile['public_repos'] ?? 0)) }}</dt>
          <dd>Public repos</dd>
        </div>
        <div>
          <dt>{{ number_format_i18n((int) ($profile['followers'] ?? 0)) }}</dt>
          <dd>Followers</dd>
        </div>
        @if ($total > 0)
          <div>
            <dt>{{ number_format_i18n($total) }}</dt>
            <dd>Contributions (yr)</dd>
          </div>
        @endif
      </dl>
    </div>
    @endif

    {{-- Contribution calendar --}}
    @if ($weeks)
    <div class="gh-cal-wrap">
      <h3 class="gh-subhead">{{ \App\field('code_cal_h2', __('Contribution activity', 'sage')) }}</h3>
      <p class="code-cal-intro">
        {{ \App\field('code_cal_intro', __('Public contributions over the last year. Darker cells are busier days.', 'sage')) }}
        @if ($total > 0)
          {{ number_format_i18n($total) }} contributions in the last year.
        @endif
      </p>
      <div class="gh-cal-scroll" tabindex="0" role="img" aria-label="GitHub contribution calendar for @{{ $login }}">
        <div class="gh-cal">
          @foreach ($weeks as $week)
            @foreach ($week as $day)
              @php
                $level = (int) ($day['level'] ?? 0);
                $date  = (string) ($day['date'] ?? '');
                $count = (int) ($day['count'] ?? 0);
                $title = $date !== '' ? sprintf('%s on %s', sprintf(_n('%s contribution', '%s contributions', $count, 'sage'), (string) $count), $date) : '';
              @endphp
              <span class="gh-day" data-level="{{ $level }}" title="{{ $title }}"></span>
            @endforeach
          @endforeach
        </div>
      </div>
      <p class="gh-cal-legend" aria-hidden="true">
        Less
        <span class="gh-day" data-level="0"></span>
        <span class="gh-day" data-level="1"></span>
        <span class="gh-day" data-level="2"></span>
        <span class="gh-day" data-level="3"></span>
        <span class="gh-day" data-level="4"></span>
        More
      </p>
    </div>
    @endif

    {{-- Featured repos --}}
    <h3 class="gh-subhead">{{ \App\field('code_feat_h2', __('Featured repositories', 'sage')) }}</h3>
    <p class="code-subintro">{{ \App\field('code_feat_intro', __('Three codebases I point people to first: a full-stack app, a WordPress plugin, and a Sage theme.', 'sage')) }}</p>
    <div class="pf-grid">
      @foreach ($repos as $r)
        @include('partials.repo-card', ['r' => $r])
      @endforeach
    </div>

    {{-- Recent activity --}}
    @if ($events)
    <h3 class="gh-subhead">{{ \App\field('code_act_h2', __('Recent activity', 'sage')) }}</h3>
    <ol class="gh-feed">
      @foreach ($events as $ev)
        <li>
          <span class="gh-feed__icon" aria-hidden="true">{!! \App\mh_svg_icon('code', 14) !!}</span>
          <a href="{{ esc_url($ev['url']) }}" rel="noopener" target="_blank">{{ $ev['text'] }}<span class="visually-hidden"> (opens in a new window)</span></a>
          @if (! empty($ev['when']))
            <time datetime="{{ esc_attr($ev['when']) }}">{{ \App\mh_github_ago($ev['when']) }}</time>
          @endif
        </li>
      @endforeach
    </ol>
    @endif

    {{-- Recently updated --}}
    @if ($live)
    <div class="code-live-head">
      <h3 class="gh-subhead">{{ \App\field('code_live_h2', __('Recently updated', 'sage')) }}</h3>
      <a class="about-text-link" href="https://github.com/{{ $login }}?tab=repositories" rel="noopener" target="_blank">
        {{ \App\field('code_live_all', __('All public repos', 'sage')) }} →
      </a>
    </div>
    <div class="pf-grid">
      @foreach ($live as $r)
        @include('partials.repo-card', ['r' => $r])
      @endforeach
    </div>
    @endif
  </div>
</section>

{{-- RESUME --}}
<section class="pf-section" id="resume" aria-labelledby="code-resume-heading" itemscope itemtype="https://schema.org/Person">
  <meta itemprop="name" content="Matt Hummel">
  <meta itemprop="jobTitle" content="WordPress Developer">
  <div class="container wide">
    <div class="code-section-head">
      <div>
        <p class="eyebrow">Experience</p>
        <h2 id="code-resume-heading" class="display-title is-section">
          {{ \App\field('code_cv_h2', __('Resume.', 'sage')) }}
        </h2>
        <p class="sec-intro">
          {{ \App\field('code_cv_intro', __('Based in Gettysburg, PA — working with shops and agencies anywhere. Open to full-time, contract, and agency overflow work.', 'sage')) }}
        </p>
      </div>
      <div class="code-resume-links">
        @if ($linkedin)
          <a class="btn" href="{{ esc_url($linkedin) }}" rel="noopener" target="_blank">
            {!! \App\mh_svg_icon('linkedin', 16) !!} LinkedIn
          </a>
        @endif
        <a class="about-text-link" href="{{ home_url('/about/') }}">Full background →</a>
      </div>
    </div>

    <ol class="resume-timeline">
      @foreach ($jobs as $job)
        @php $current = strcasecmp((string) $job['period'], 'Current') === 0; @endphp
        <li>
          <article class="resume-card{{ $current ? ' is-current' : '' }}" itemscope itemtype="https://schema.org/OrganizationRole">
            <header class="resume-head">
              <div class="resume-who">
                @if ($current)
                  <p class="resume-now">Current</p>
                @endif
                <h3 itemprop="roleName">{{ $job['role'] }}</h3>
                @if ($job['org'] !== '')
                  <p class="resume-org">
                    {!! \App\mh_svg_icon('briefcase', 15) !!}
                    @if (! empty($job['url']))
                      <a href="{{ esc_url($job['url']) }}" rel="noopener" target="_blank" itemprop="worksFor">{{ $job['org'] }}<span class="visually-hidden"> (opens in a new window)</span></a>
                    @else
                      <span itemprop="worksFor">{{ $job['org'] }}</span>
                    @endif
                  </p>
                @endif
              </div>
              <p class="resume-tags">
                @if ($job['period'] !== '')
                  <span class="resume-tag{{ $current ? ' is-now' : '' }}">{{ $job['period'] }}</span>
                @endif
                @if ($job['type'] !== '')
                  <span class="resume-tag">{{ $job['type'] }}</span>
                @endif
              </p>
            </header>
            @if ($job['bullets'])
              <ul class="resume-points">
                @foreach ($job['bullets'] as $b)
                  <li>{{ $b }}</li>
                @endforeach
              </ul>
            @endif
          </article>
        </li>
      @endforeach
    </ol>
  </div>
</section>

{{-- SKILLS --}}
<section class="pf-section pf-section--alt" id="skills" aria-labelledby="code-skills-heading">
  <div class="container wide">
    <p class="eyebrow">Tools</p>
    <h2 id="code-skills-heading" class="display-title is-section">
      {{ \App\field('code_sk_h2', __('Skills and tools.', 'sage')) }}
    </h2>
    <p class="sec-intro">
      {{ \App\field('code_sk_intro', __('Tools I reach for on shipped work. Not an exhaustive list — just the things I actually use.', 'sage')) }}
    </p>
    <ul class="skill-row" style="margin-top:1.5rem">
      @foreach ($skills as $skill)
        <li>{!! \App\mh_skill_chip($skill) !!}</li>
      @endforeach
    </ul>
  </div>
</section>

{{-- DOCUMENTATION --}}
<section class="pf-section" id="docs" aria-labelledby="code-docs-heading">
  <div class="container wide">
    <p class="eyebrow">Reference</p>
    <h2 id="code-docs-heading" class="display-title is-section">
      {{ \App\field('code_doc_h2', __('Documentation I keep open.', 'sage')) }}
    </h2>
    <p class="sec-intro">
      {{ \App\field('code_doc_intro', __('Official handbooks first, then the Roots and front-end stack this site is built on. All links open official docs.', 'sage')) }}
    </p>
    <ul class="doc-grid" style="margin-top:1.5rem">
      @foreach ($docs as $doc)
        <li>
          <a href="{{ esc_url($doc['url']) }}" rel="noopener" target="_blank">
            {{ $doc['label'] }}<span class="visually-hidden"> (opens in a new window)</span>
          </a>
          @if ($doc['note'] !== '')
            <p>{{ $doc['note'] }}</p>
          @endif
        </li>
      @endforeach
    </ul>
  </div>
</section>

{{-- CTA --}}
<section class="cta-band" aria-labelledby="code-cta-heading">
  <div class="container wide cta-band-inner">
    <div>
      <p class="eyebrow eyebrow--on-dark">Work together</p>
      <h2 id="code-cta-heading" class="display-title is-section">See something useful?</h2>
      <p>Fork a repo, copy a snippet, or write if you want to work together. A question about a line of code is just as welcome as a project.</p>
    </div>
    <div style="display:flex;flex-direction:column;gap:.75rem;align-items:flex-end;flex-shrink:0">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!} Say hello
      </a>
      <a class="about-text-link" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank" style="color:#9ca3af">
        {!! \App\mh_svg_icon('github', 14) !!} @matthummel-pa →
      </a>
    </div>
  </div>
</section>

@endsection
