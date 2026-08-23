{{--
  Template Name: Code
--}}
@extends('layouts.app')

@section('content')
@php
  $profile = \App\mh_github_profile();
  $calendar = \App\mh_github_calendar();
  $events = \App\mh_github_events(10);
  $repos = \App\mh_code_page_repos();
  $live = \App\mh_github_live_repos(6);
  $practice = \App\mh_code_page_practice();
  $jobs = \App\mh_code_page_resume();
  $skills = \App\mh_code_page_skills();
  $docs = \App\mh_code_page_resources();
  $login = \App\mh_github_login();
  $weeks = $calendar['weeks'] ?? [];
  $total = (int) ($calendar['total'] ?? 0);
@endphp

@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('code_kicker', __('Engineering', 'sage')) }}</p>
  <h1 class="display-title is-hero">{{ \App\field('code_h1', __('What I do', 'sage')) }}</h1>
  <p class="lead">{!! \App\field_html('code_lede', __('I build and maintain WordPress sites, plugins, and other web applications from Gettysburg, Pennsylvania, for shops and agencies anywhere. Most of that work is Sage, Blade, PHP, and front-end architecture they can keep editing. I also keep a public GitHub profile so other developers can read the same code I ship.', 'sage')) !!}</p>
@endcomponent

<section class="pf-section">
  <div class="container wide">
    <h2 class="display-title is-section">{{ \App\field('code_do_h2', __('Practice', 'sage')) }}</h2>
    <p class="sec-intro">{{ \App\field('code_do_intro', __('WordPress is the public focus. I also write React apps and do some Microsoft Power Platform work when a team already lives in that stack.', 'sage')) }}</p>
    <ul class="practice-list">
      @foreach ($practice as $item)
        <li>{{ $item }}</li>
      @endforeach
    </ul>
  </div>
</section>

<section class="pf-section pf-section--alt" id="github">
  <div class="container wide">
    <h2 class="display-title is-section">{{ \App\field('code_gh_h2', __('GitHub', 'sage')) }}</h2>

    @if (! empty($profile['login']))
    <div class="gh-profile">
      @if (! empty($profile['avatar']))
        <img class="gh-avatar" src="{{ esc_url($profile['avatar']) }}" width="88" height="88" alt="" decoding="async">
      @endif
      <div class="gh-profile-copy">
        <p class="gh-name">{{ $profile['name'] ?: $profile['login'] }}</p>
        <p class="gh-login">
          <a href="{{ esc_url($profile['url'] ?: 'https://github.com/'.$login) }}" rel="noopener" target="_blank">{{ '@'.$profile['login'] }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
          @if (! empty($profile['location']))
            <span class="gh-loc">{{ $profile['location'] }}</span>
          @endif
        </p>
        @if (! empty($profile['bio']))
          <p class="gh-bio">{{ $profile['bio'] }}</p>
        @endif
      </div>
      <dl class="stat-row gh-stats">
        <div>
          <dt>{{ number_format_i18n((int) ($profile['public_repos'] ?? 0)) }}</dt>
          <dd>{{ __('Public repos', 'sage') }}</dd>
        </div>
        <div>
          <dt>{{ number_format_i18n((int) ($profile['followers'] ?? 0)) }}</dt>
          <dd>{{ __('Followers', 'sage') }}</dd>
        </div>
        <div>
          <dt>{{ number_format_i18n((int) ($profile['following'] ?? 0)) }}</dt>
          <dd>{{ __('Following', 'sage') }}</dd>
        </div>
      </dl>
    </div>
    @endif

    @if ($weeks)
    <div class="gh-cal-wrap">
      <h3 class="gh-subhead">{{ \App\field('code_cal_h2', __('Contribution activity', 'sage')) }}</h3>
      <p>{{ \App\field('code_cal_intro', __('Public contributions over the last year. Darker cells are busier days.', 'sage')) }}
        @if ($total > 0)
          {{ sprintf(__('%s contributions in the last year.', 'sage'), number_format_i18n($total)) }}
        @endif
      </p>
      <div class="gh-cal-scroll" tabindex="0" role="img" aria-label="{{ sprintf(__('GitHub contribution calendar for %s', 'sage'), $login) }}">
        <div class="gh-cal">
          @foreach ($weeks as $week)
            @foreach ($week as $day)
              @php
                $level = (int) ($day['level'] ?? 0);
                $date = (string) ($day['date'] ?? '');
                $count = (int) ($day['count'] ?? 0);
                $title = $date !== '' ? sprintf(__('%1$s on %2$s', 'sage'), sprintf(_n('%s contribution', '%s contributions', $count, 'sage'), (string) $count), $date) : '';
              @endphp
              <span class="gh-day" data-level="{{ $level }}" title="{{ $title }}"></span>
            @endforeach
          @endforeach
        </div>
      </div>
      <p class="gh-cal-legend" aria-hidden="true">
        <span>{{ __('Less', 'sage') }}</span>
        <span class="gh-day" data-level="0"></span>
        <span class="gh-day" data-level="1"></span>
        <span class="gh-day" data-level="2"></span>
        <span class="gh-day" data-level="3"></span>
        <span class="gh-day" data-level="4"></span>
        <span>{{ __('More', 'sage') }}</span>
      </p>
    </div>
    @endif

    <h3 class="gh-subhead">{{ \App\field('code_feat_h2', __('Featured repositories', 'sage')) }}</h3>
    <p>{{ \App\field('code_feat_intro', __('Three codebases I point people to first: a full-stack app, a WordPress plugin, and a Sage theme.', 'sage')) }}</p>
    <div class="pf-grid">
      @foreach ($repos as $r)
        @include('partials.repo-card', ['r' => $r])
      @endforeach
    </div>

    @if ($events)
    <h3 class="gh-subhead">{{ \App\field('code_act_h2', __('Recent activity', 'sage')) }}</h3>
    <ol class="gh-feed">
      @foreach ($events as $ev)
        <li>
          <a href="{{ esc_url($ev['url']) }}" rel="noopener" target="_blank">{{ $ev['text'] }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
          @if (! empty($ev['when']))
            <time datetime="{{ esc_attr($ev['when']) }}">{{ \App\mh_github_ago($ev['when']) }}</time>
          @endif
        </li>
      @endforeach
    </ol>
    @endif

    @if ($live)
    <h3 class="gh-subhead">{{ \App\field('code_live_h2', __('Recently updated', 'sage')) }}</h3>
    <div class="pf-grid">
      @foreach ($live as $r)
        @include('partials.repo-card', ['r' => $r])
      @endforeach
    </div>
    <p><a href="https://github.com/{{ $login }}?tab=repositories" rel="noopener" target="_blank">{{ \App\field('code_live_all', __('All public repositories', 'sage')) }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a></p>
    @endif
  </div>
</section>

<section class="pf-section" id="resume">
  <div class="container wide">
    <h2 class="display-title is-section">{{ \App\field('code_cv_h2', __('Resume', 'sage')) }}</h2>
    <p class="sec-intro">{{ \App\field('code_cv_intro', __('Based in Gettysburg, PA. I just started Ridges & Valleys and I work with shops and agencies in any location. Roles below match my LinkedIn. I am still open to agencies, overflow work, and full-time positions.', 'sage')) }}</p>
    @php
      $linkedin = \App\mh_portfolio_social_defaults()['linkedin'] ?? '';
    @endphp
    @if ($linkedin !== '')
      <p class="resume-links">
        <a href="{{ esc_url($linkedin) }}" rel="noopener" target="_blank">{!! \App\mh_svg_icon('linkedin', 16) !!} {{ __('LinkedIn profile', 'sage') }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
      </p>
    @endif
    <ol class="resume-timeline">
      @foreach ($jobs as $job)
        @php
          $current = strcasecmp((string) $job['period'], 'Current') === 0;
        @endphp
        <li>
          <article class="resume-card{{ $current ? ' is-current' : '' }}">
            <header class="resume-head">
              <div class="resume-who">
                @if ($current)
                  <p class="resume-now">{{ __('Current role', 'sage') }}</p>
                @endif
                <h3>{{ $job['role'] }}</h3>
                @if ($job['org'] !== '')
                  <p class="resume-org">
                    {!! \App\mh_svg_icon('briefcase', 16) !!}
                    @if (! empty($job['url']))
                      <a href="{{ esc_url($job['url']) }}" rel="noopener" target="_blank">{{ $job['org'] }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
                    @else
                      <span>{{ $job['org'] }}</span>
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

<section class="pf-section pf-section--alt" id="skills">
  <div class="container wide">
    <h2 class="display-title is-section">{{ \App\field('code_sk_h2', __('Skills', 'sage')) }}</h2>
    <p>{{ \App\field('code_sk_intro', __('Tools I use on shipped work. Icons match the brands other developers already recognize.', 'sage')) }}</p>
    <ul class="skill-row">
      @foreach ($skills as $skill)
        <li>{!! \App\mh_skill_chip($skill) !!}</li>
      @endforeach
    </ul>
  </div>
</section>

<section class="pf-section" id="docs">
  <div class="container wide">
    <h2 class="display-title is-section">{{ \App\field('code_doc_h2', __('Documentation I use', 'sage')) }}</h2>
    <p class="sec-intro">{{ \App\field('code_doc_intro', __('Reference docs I keep open while I work. Official handbooks first, then the Roots and front-end stack this theme is built on.', 'sage')) }}</p>
    <ul class="doc-grid">
      @foreach ($docs as $doc)
        <li>
          <a href="{{ esc_url($doc['url']) }}" rel="noopener" target="_blank">{{ $doc['label'] }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
          @if ($doc['note'] !== '')
            <p>{{ $doc['note'] }}</p>
          @endif
        </li>
      @endforeach
    </ul>
  </div>
</section>
@endsection
