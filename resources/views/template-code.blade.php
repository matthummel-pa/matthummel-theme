{{--
  Template Name: Code
--}}
@extends('layouts.app')

@section('content')
@php
  $profile  = \App\mh_github_profile();
  $calendarYear = \App\mh_github_calendar();
  $calendar = \App\mh_github_calendar_recent(30);
  $events   = \App\mh_github_events_recent(8, 30);
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
  $yearTotal = (int) ($calendarYear['total'] ?? 0);
  $linkedin = \App\mh_portfolio_social_defaults()['linkedin'] ?? '';
@endphp

{{-- HERO --}}
@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('code_kicker', __('Code', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('code_h1', __('PHP, WordPress, and open-source code.', 'sage')) }}
  </h1>
  <p class="lead">
    {!! \App\field_html('code_lede', __('Most of my work is public on GitHub — repos you can fork, snippets you can paste, and themes written so any developer can read them without asking me first. Resume and skill chips below.', 'sage')) !!}
  </p>
  @if (\App\mh_is_hireable($profile))
    <p class="hire-avail" style="margin-top:.85rem">
      @include('partials.avail-mark', ['gh' => $profile])
      {{ \App\mh_availability_label($profile, __('Open for new work', 'sage')) }} — full-time, contract, agency overflow
    </p>
  @endif
  <p class="about-hero-links" style="margin-top:1rem">
    <a href="#github">{!! \App\mh_svg_icon('git', 15) !!} {{ __('Open source', 'sage') }}</a>
    <a href="#resume">{!! \App\mh_svg_icon('briefcase', 15) !!} {{ __('Resume', 'sage') }}</a>
    <a href="#skills">{!! \App\mh_svg_icon('code', 15) !!} {{ __('Skills', 'sage') }}</a>
    <a href="#docs">{!! \App\mh_svg_icon('globe', 15) !!} {{ __('Docs', 'sage') }}</a>
    <a href="#hire">{!! \App\mh_svg_icon('mail', 15) !!} {{ __('Hire me', 'sage') }}</a>
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

{{-- GITHUB / OPEN SOURCE --}}
@php
  $calMonths = \App\mh_github_calendar_months($weeks);
  $weekCount = max(1, count($weeks));
@endphp
<section class="pf-section pf-section--alt code-gh" id="github" aria-labelledby="code-gh-heading">
  <div class="container wide">
    <div class="code-gh__head">
      <div>
        <p class="eyebrow">{{ __('Open source', 'sage') }}</p>
        <h2 id="code-gh-heading" class="display-title is-section">
          {{ \App\field('code_gh_h2', __('Open-source WordPress code on GitHub.', 'sage')) }}
        </h2>
        <p class="sec-intro">
          {{ \App\field('code_gh_intro', __('Public repos from my Gettysburg studio — Sage themes, WordPress plugins, and other web apps shops and developers can fork. Live stats pull from the GitHub API.', 'sage')) }}
        </p>
      </div>
      <nav class="code-gh__jump" aria-label="{{ __('Jump to GitHub sections', 'sage') }}">
        @if (! empty($profile['login']))
          <a href="#gh-profile">{{ __('Profile', 'sage') }}</a>
        @endif
        @if ($weeks)
          <a href="#gh-contributions">{{ __('Contributions', 'sage') }}</a>
        @endif
        <a href="#gh-featured">{{ __('Featured', 'sage') }}</a>
        @if ($events)
          <a href="#gh-activity">{{ __('Activity', 'sage') }}</a>
        @endif
        @if ($live)
          <a href="#gh-updated">{{ __('Updated', 'sage') }}</a>
        @endif
      </nav>
    </div>

    {{-- Profile showcase --}}
    @if (! empty($profile['login']))
    <div class="code-gh-profile" id="gh-profile">
      <div class="code-gh-profile__mesh" aria-hidden="true"></div>
      <div class="code-gh-profile__main">
        <div class="code-gh-profile__who">
          @if (! empty($profile['avatar']))
            <img class="code-gh-profile__avatar" src="{{ esc_url($profile['avatar']) }}" width="96" height="96" alt="{{ esc_attr(($profile['name'] ?: $profile['login']).' GitHub avatar') }}" loading="lazy" decoding="async">
          @else
            <span class="code-gh-profile__avatar code-gh-profile__avatar--fallback" aria-hidden="true">{!! \App\mh_svg_icon('github', 36) !!}</span>
          @endif
          <div class="code-gh-profile__copy">
            <p class="code-gh-profile__name">{{ $profile['name'] ?: $profile['login'] }}</p>
            <p class="code-gh-profile__meta">
              <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
                {!! \App\mh_svg_icon('github', 14) !!}
                {{ '@'.$login }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
              </a>
              @if (! empty($profile['location']))
                <span>{!! \App\mh_svg_icon('map', 13) !!} {{ $profile['location'] }}</span>
              @endif
              @if (! empty($profile['created']))
                <span>{{ sprintf(__('On GitHub since %s', 'sage'), $profile['created']) }}</span>
              @endif
            </p>
            @if (! empty($profile['bio']))
              <p class="code-gh-profile__bio">{{ $profile['bio'] }}</p>
            @endif
            <p class="code-gh-profile__actions">
              <a class="btn" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
                {!! \App\mh_svg_icon('github', 15) !!} {{ __('View on GitHub', 'sage') }}
                <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
              </a>
              <span class="code-gh-live" aria-label="{{ __('Live data from GitHub API', 'sage') }}">
                <span class="h-badge__dot" aria-hidden="true"></span>
                {{ __('Live from API', 'sage') }}
              </span>
            </p>
          </div>
        </div>
        <dl class="code-gh-stats">
          <div class="code-gh-stat">
            <dt>
              <span class="code-gh-stat__ico" aria-hidden="true">{!! \App\mh_svg_icon('code', 16) !!}</span>
              {{ number_format_i18n((int) ($profile['public_repos'] ?? 0)) }}
            </dt>
            <dd>{{ __('Public repos', 'sage') }}</dd>
          </div>
          <div class="code-gh-stat">
            <dt>
              <span class="code-gh-stat__ico" aria-hidden="true">{!! \App\mh_svg_icon('github', 16) !!}</span>
              {{ number_format_i18n((int) ($profile['followers'] ?? 0)) }}
            </dt>
            <dd>{{ __('Followers', 'sage') }}</dd>
          </div>
          @if ($yearTotal > 0)
            <div class="code-gh-stat">
              <dt>
                <span class="code-gh-stat__ico" aria-hidden="true">{!! \App\mh_svg_icon('git', 16) !!}</span>
                {{ number_format_i18n($yearTotal) }}
              </dt>
              <dd>{{ __('Contributions (yr)', 'sage') }}</dd>
            </div>
          @endif
        </dl>
      </div>
    </div>
    @endif

    {{-- Contributions + activity --}}
    @if ($weeks || $events)
    <div class="code-gh-split">
      @if ($weeks)
      <div class="code-gh-panel code-gh-cal" id="gh-contributions">
        <div class="code-gh-panel__head">
          <span class="code-gh-panel__mark" aria-hidden="true">{!! \App\mh_svg_icon('git', 18) !!}</span>
          <div>
            <h3 class="code-gh-panel__title">{{ \App\field('code_cal_h2', __('Last 30 days of commits', 'sage')) }}</h3>
            <p class="code-gh-panel__intro">
              {{ \App\field('code_cal_intro', __('Contribution heat map for the last 30 days, newest week first. Darker blue means a busier day on public repos.', 'sage')) }}
              @if ($total > 0)
                <strong>{{ sprintf(__('%s contributions in the last 30 days.', 'sage'), number_format_i18n($total)) }}</strong>
              @endif
            </p>
          </div>
        </div>
        <div class="gh-cal-scroll" tabindex="0" role="img" aria-label="{{ sprintf(__('GitHub contribution calendar for @%s', 'sage'), $login) }}">
          @if ($calMonths)
            <div class="code-gh-cal__months" aria-hidden="true" style="--gh-weeks: {{ $weekCount }}">
              @foreach ($calMonths as $m)
                <span style="grid-column: {{ $m['week'] + 1 }}">{{ $m['label'] }}</span>
              @endforeach
            </div>
          @endif
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
          {{ __('Less', 'sage') }}
          <span class="gh-day" data-level="0"></span>
          <span class="gh-day" data-level="1"></span>
          <span class="gh-day" data-level="2"></span>
          <span class="gh-day" data-level="3"></span>
          <span class="gh-day" data-level="4"></span>
          {{ __('More', 'sage') }}
        </p>
      </div>
      @endif

      @if ($events)
      <div class="code-gh-panel code-gh-activity" id="gh-activity">
        <div class="code-gh-panel__head">
          <span class="code-gh-panel__mark" aria-hidden="true">{!! \App\mh_svg_icon('code', 18) !!}</span>
          <div>
            <h3 class="code-gh-panel__title">{{ \App\field('code_act_h2', __('What shipped lately', 'sage')) }}</h3>
            <p class="code-gh-panel__intro">{{ \App\field('code_act_intro', __('Pushes, releases, and pull requests from the last 30 days — newest first.', 'sage')) }}</p>
          </div>
        </div>
        <ol class="code-gh-feed">
          @foreach ($events as $ev)
            @php $evIcon = \App\mh_github_event_icon((string) ($ev['type'] ?? '')); @endphp
            <li class="code-gh-feed__item" data-type="{{ esc_attr((string) ($ev['type'] ?? '')) }}">
              <span class="code-gh-feed__icon" aria-hidden="true">{!! \App\mh_svg_icon($evIcon, 14) !!}</span>
              <span class="code-gh-feed__text">
                <a href="{{ esc_url($ev['url']) }}" rel="noopener" target="_blank">{{ $ev['text'] }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
              </span>
              @if (! empty($ev['when']))
                <time datetime="{{ esc_attr($ev['when']) }}">{{ \App\mh_github_ago($ev['when']) }}</time>
              @endif
            </li>
          @endforeach
        </ol>
      </div>
      @endif
    </div>
    @endif

    {{-- Featured repos --}}
    <div class="code-gh-block" id="gh-featured">
      <div class="code-gh-block__head">
        <div>
          <h3 class="code-gh-block__title">{{ \App\field('code_feat_h2', __('Repos worth opening first', 'sage')) }}</h3>
          <p class="code-subintro">{{ \App\field('code_feat_intro', __('Three codebases I point people to first: a full-stack app, a WordPress plugin, and the Sage theme behind this site.', 'sage')) }}</p>
        </div>
      </div>
      <div class="pf-grid code-gh-repos code-gh-repos--featured">
        @foreach ($repos as $i => $r)
          @include('partials.repo-card', ['r' => $r, 'index' => $i + 1, 'featured' => true])
        @endforeach
      </div>
    </div>

    {{-- Recently updated --}}
    @if ($live)
    <div class="code-gh-block" id="gh-updated">
      <div class="code-gh-block__head code-live-head">
        <div>
          <h3 class="code-gh-block__title">{{ \App\field('code_live_h2', __('Recently pushed', 'sage')) }}</h3>
          <p class="code-subintro">{{ \App\field('code_live_intro', __('Latest public updates across my GitHub account — useful if you want to see what I am actively touching.', 'sage')) }}</p>
        </div>
        <a class="about-text-link" href="https://github.com/{{ esc_attr($login) }}?tab=repositories" rel="noopener" target="_blank">
          {{ \App\field('code_live_all', __('All public repositories', 'sage')) }} →
          <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
        </a>
      </div>
      <div class="pf-grid code-gh-repos">
        @foreach ($live as $r)
          @include('partials.repo-card', ['r' => $r])
        @endforeach
      </div>
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
<section class="cta-band" id="hire" aria-labelledby="code-cta-heading">
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
