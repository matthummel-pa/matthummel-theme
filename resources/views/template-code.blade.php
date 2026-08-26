{{--
  Template Name: Code
--}}
@extends('layouts.app')

@section('content')
@php
  $profile  = \App\mh_github_profile();
  $calendarYear = \App\mh_github_calendar();
  $calendar = \App\mh_github_calendar_recent(90);
  $events   = \App\mh_github_events_recent(10, 90);
  $eventsByDay = \App\mh_github_events_by_day(90);
  $repos    = \App\mh_code_page_repos();
  $live     = \App\mh_github_live_repos(6);
  $practice = \App\mh_code_page_practice();
  $skills   = \App\mh_code_page_skills();
  $docGroups = \App\mh_code_page_resources_grouped();
  $login    = \App\mh_github_login();
  $ghUrl    = $profile['url'] ?: 'https://github.com/'.$login;
  $weeks    = $calendar['weeks'] ?? [];
  $total    = (int) ($calendar['total'] ?? 0);
  $yearTotal = (int) ($calendarYear['total'] ?? 0);
@endphp

{{-- HERO --}}
@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('code_kicker', __('Code', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('code_h1', __('PHP, WordPress, and open-source code.', 'sage')) }}
  </h1>
  <p class="lead">
    {!! \App\field_html('code_lede', __('Most of my work is public on GitHub — repos you can fork, snippets you can paste, and themes written so any developer can read them without asking me first.', 'sage')) !!}
  </p>
  @if (\App\mh_is_hireable($profile))
    <p class="hire-avail" style="margin-top:.85rem">
      @include('partials.avail-mark', ['gh' => $profile])
      {{ \App\mh_availability_label($profile, __('Open for new work', 'sage')) }} — full-time, contract, agency overflow
    </p>
  @endif
  <p class="about-hero-links" style="margin-top:1rem">
    <a href="#github">{!! \App\mh_svg_icon('git', 15) !!} {{ __('Open source', 'sage') }}</a>
    <a href="#skills">{!! \App\mh_svg_icon('code', 15) !!} {{ __('Skills', 'sage') }}</a>
    <a href="#docs">{!! \App\mh_svg_icon('globe', 15) !!} {{ __('Docs', 'sage') }}</a>
    <a href="{{ home_url('/hire/') }}">{!! \App\mh_svg_icon('mail', 15) !!} {{ __('Hire me', 'sage') }}</a>
  </p>
@endcomponent

{{-- PRACTICE --}}
<section class="pf-section code-practice-sec" aria-labelledby="code-practice-heading">
  <div class="container wide">
    <div class="code-section-head">
      <div>
        <p class="eyebrow">{{ __('Day to day', 'sage') }}</p>
        <h2 id="code-practice-heading" class="display-title is-section">
          {{ \App\field('code_do_h2', __('What I work on.', 'sage')) }}
        </h2>
        <p class="sec-intro">
          {{ \App\field('code_do_intro', __('WordPress is the main focus from my Gettysburg studio. Most projects are Sage, PHP, and front-end work shops can keep editing after I hand off. I also write React apps and do Power Platform work when a team lives in Microsoft 365.', 'sage')) }}
        </p>
      </div>
      <div class="code-section-links">
        <a class="about-text-link" href="{{ home_url('/services/') }}">{{ __('Services page', 'sage') }} →</a>
        <a class="about-text-link" href="{{ home_url('/projects/') }}">{{ __('Example sites', 'sage') }} →</a>
      </div>
    </div>
    <ul class="code-practice">
      @foreach ($practice as $i => $item)
        <li class="code-practice__item">
          <span class="code-practice__n" aria-hidden="true">{{ sprintf('%02d', $i + 1) }}</span>
          <p class="code-practice__text">{{ $item }}</p>
        </li>
      @endforeach
    </ul>
  </div>
</section>

{{-- GITHUB / OPEN SOURCE --}}
@php
  $calMonths = \App\mh_github_calendar_months($weeks);
  $weekCount = max(1, count($weeks));
  $calWindow = (int) ($calendar['days'] ?? 90);
  $dayIndex = 0;
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
          {{ \App\field('code_gh_intro', __('Public repos from my Gettysburg studio — Sage themes, WordPress plugins, and web apps shops and developers can fork. Stats and activity below pull live from the GitHub API.', 'sage')) }}
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
      <div class="code-gh-panel code-gh-cal" id="gh-contributions" style="--gh-weeks: {{ $weekCount }}">
        <div class="code-gh-panel__head">
          <span class="code-gh-panel__mark" aria-hidden="true">{!! \App\mh_svg_icon('git', 18) !!}</span>
          <div>
            <h3 class="code-gh-panel__title">{{ \App\field('code_cal_h2', __('Last 90 days of commits', 'sage')) }}</h3>
            <p class="code-gh-panel__intro">
              {{ \App\field('code_cal_intro', __('Contribution heat map for the last 90 days, newest week first. Hover a day to see what shipped. Darker blue means a busier day on public repos.', 'sage')) }}
              @if ($total > 0)
                <strong>{{ sprintf(__('%s contributions in the last %s days.', 'sage'), number_format_i18n($total), number_format_i18n($calWindow)) }}</strong>
              @endif
            </p>
          </div>
        </div>
        <div class="gh-cal-scroll" tabindex="0" aria-label="{{ sprintf(__('GitHub contribution calendar for @%s — hover or focus a day for details', 'sage'), $login) }}">
          @if ($calMonths)
            <div class="code-gh-cal__months" aria-hidden="true">
              @foreach ($calMonths as $m)
                <span style="grid-column: {{ $m['week'] + 1 }}">{{ $m['label'] }}</span>
              @endforeach
            </div>
          @endif
          <div class="gh-cal">
            @foreach ($weeks as $week)
              <div class="gh-week">
                @foreach ($week as $day)
                  @php
                    $level = (int) ($day['level'] ?? 0);
                    $date  = (string) ($day['date'] ?? '');
                    $count = (int) ($day['count'] ?? 0);
                    $dayEvents = $date !== '' ? ($eventsByDay[$date] ?? []) : [];
                    $tip = $date !== '' ? \App\mh_github_day_tip($date, $count, $dayEvents) : '';
                    $i = $dayIndex++;
                  @endphp
                  @if ($date !== '')
                    <button
                      type="button"
                      class="gh-day"
                      data-level="{{ $level }}"
                      style="--i: {{ $i }}"
                      aria-label="{{ esc_attr($tip) }}"
                    >
                      <span class="gh-day__tip" role="tooltip">{{ $tip }}</span>
                    </button>
                  @else
                    <span class="gh-day gh-day--pad" data-level="0" style="--i: {{ $i }}" aria-hidden="true"></span>
                  @endif
                @endforeach
              </div>
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
            <h3 class="code-gh-panel__title">{{ \App\field('code_act_h2', __('Public activity', 'sage')) }}</h3>
            <p class="code-gh-panel__intro">{{ \App\field('code_act_intro', __('Pushes, releases, and pull requests from the last 90 days — newest first. Open any row to jump into the repo.', 'sage')) }}</p>
          </div>
        </div>
        <ol class="code-gh-feed">
          @foreach ($events as $ev)
            @php
              $evIcon = \App\mh_github_event_icon((string) ($ev['type'] ?? ''));
              $evType = (string) ($ev['type'] ?? '');
              $evRepo = (string) ($ev['repo'] ?? '');
            @endphp
            <li class="code-gh-feed__item" data-type="{{ esc_attr($evType) }}">
              <span class="code-gh-feed__icon" aria-hidden="true">{!! \App\mh_svg_icon($evIcon, 14) !!}</span>
              <div class="code-gh-feed__body">
                <a class="code-gh-feed__link" href="{{ esc_url($ev['url']) }}" rel="noopener" target="_blank">
                  {{ $ev['text'] }}
                  <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
                </a>
                @if ($evRepo !== '')
                  <span class="code-gh-feed__repo">{!! \App\mh_svg_icon('github', 12) !!} {{ $evRepo }}</span>
                @endif
              </div>
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
    <div class="code-gh-block code-gh-block--featured" id="gh-featured">
      <div class="code-gh-block__head">
        <div class="code-gh-block__copy">
          <p class="eyebrow">{{ __('Featured', 'sage') }}</p>
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
    <div class="code-gh-block code-gh-block--live" id="gh-updated">
      <div class="code-gh-block__head code-live-head">
        <div class="code-gh-block__copy">
          <p class="eyebrow">{{ __('Pulse', 'sage') }}</p>
          <h3 class="code-gh-block__title">{{ \App\field('code_live_h2', __('Recently pushed', 'sage')) }}</h3>
          <p class="code-subintro">{{ \App\field('code_live_intro', __('Latest public updates across my GitHub account — useful if you want to see what I am actively touching.', 'sage')) }}</p>
        </div>
        <a class="btn btn-outline code-gh-block__cta" href="https://github.com/{{ esc_attr($login) }}?tab=repositories" rel="noopener" target="_blank">
          {!! \App\mh_svg_icon('github', 14) !!}
          {{ \App\field('code_live_all', __('All public repositories', 'sage')) }}
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

{{-- SKILLS --}}
<section class="pf-section code-skills-sec" id="skills" aria-labelledby="code-skills-heading">
  <div class="container wide">
    <div class="code-skills">
      <div class="code-skills__mesh" aria-hidden="true"></div>
      <div class="code-skills__inner">
        <p class="eyebrow">{{ __('Tools', 'sage') }}</p>
        <h2 id="code-skills-heading" class="display-title is-section">
          {{ \App\field('code_sk_h2', __('Skills and tools.', 'sage')) }}
        </h2>
        <p class="sec-intro">
          {{ \App\field('code_sk_intro', __('Tools I reach for on shipped WordPress and web work. Not an exhaustive list — just what shows up in real repos.', 'sage')) }}
        </p>
        <ul class="skill-row code-skills__row">
          @foreach ($skills as $skill)
            <li>{!! \App\mh_skill_chip($skill) !!}</li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
</section>

{{-- DOCUMENTATION --}}
<section class="pf-section code-docs-sec" id="docs" aria-labelledby="code-docs-heading">
  <div class="container wide">
    <div class="code-docs-shell">
      <div class="code-docs-shell__mesh" aria-hidden="true"></div>
      <div class="code-docs-shell__inner">
        <div class="code-section-head code-docs-shell__head">
          <div>
            <p class="eyebrow">{{ __('Reference', 'sage') }}</p>
            <h2 id="code-docs-heading" class="display-title is-section">
              {{ \App\field('code_doc_h2', __('Documentation I keep open.', 'sage')) }}
            </h2>
            <p class="sec-intro">
              {{ \App\field('code_doc_intro', __('Official handbooks first, then the Roots and front-end stack this site is built on. Grouped so you can jump to the right shelf. All links open official docs.', 'sage')) }}
            </p>
          </div>
          @if (count($docGroups) > 1)
            <nav class="code-docs-jump" aria-label="{{ __('Documentation groups', 'sage') }}">
              @foreach ($docGroups as $group)
                <a href="#doc-{{ sanitize_title($group['label']) }}">{{ $group['label'] }}</a>
              @endforeach
            </nav>
          @endif
        </div>

        <div class="code-docs-groups">
          @foreach ($docGroups as $group)
            <section class="code-docs-group" id="doc-{{ sanitize_title($group['label']) }}" aria-labelledby="doc-heading-{{ sanitize_title($group['label']) }}">
              <div class="code-docs-group__head">
                <span class="code-docs-group__mark" aria-hidden="true">{!! \App\mh_svg_icon($group['icon'], 16) !!}</span>
                <h3 id="doc-heading-{{ sanitize_title($group['label']) }}" class="code-docs-group__title">{{ $group['label'] }}</h3>
                <span class="code-docs-group__count">{{ number_format_i18n(count($group['items'])) }}</span>
              </div>
              <ul class="code-docs">
                @foreach ($group['items'] as $doc)
                  <li class="code-docs__card" data-group="{{ esc_attr($doc['group']) }}">
                    <a class="code-docs__hit" href="{{ esc_url($doc['url']) }}" rel="noopener" target="_blank">
                      <span class="code-docs__mark" aria-hidden="true">{!! \App\mh_svg_icon($doc['icon'], 18) !!}</span>
                      <span class="code-docs__copy">
                        <span class="code-docs__title">
                          {{ $doc['label'] }}
                          <span class="code-docs__ext" aria-hidden="true">↗</span>
                        </span>
                        @if (($doc['note'] ?? '') !== '')
                          <span class="code-docs__note">{{ $doc['note'] }}</span>
                        @endif
                        @if (($doc['host'] ?? '') !== '')
                          <span class="code-docs__host">{{ $doc['host'] }}</span>
                        @endif
                      </span>
                      <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
                    </a>
                  </li>
                @endforeach
              </ul>
            </section>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

{{-- CTA --}}
<section class="cta-band code-cta" aria-labelledby="code-cta-heading">
  <div class="container wide cta-band-inner">
    <div class="code-cta__copy">
      <p class="eyebrow eyebrow--on-dark">{{ \App\field('code_cta_kicker', __('Work together', 'sage')) }}</p>
      <h2 id="code-cta-heading" class="display-title is-section">
        {{ \App\field('code_cta_h2', __('Need WordPress help in Gettysburg?', 'sage')) }}
      </h2>
      <p>{{ \App\field('code_cta_lede', __('Fork a repo, copy a snippet, or write if you want to work together. A question about a line of code is just as welcome as a project.', 'sage')) }}</p>
    </div>
    <div class="code-cta__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/hire/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ \App\field('code_cta_btn', __('Hire me', 'sage')) }}
      </a>
      <a class="code-cta__gh" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
        {!! \App\mh_svg_icon('github', 14) !!}
        {{ '@'.$login }} →
        <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
      </a>
    </div>
  </div>
</section>

@endsection
