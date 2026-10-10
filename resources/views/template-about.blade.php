{{--
  Template Name: About
--}}
{{-- About, Hire, and Code in one page (3.6.49). /hire/ and /code/ 301 to #hire and #code. --}}
@extends('layouts.app')

@php
  $login       = \App\mh_github_login();
  $gh          = \App\Github::fetchUser($login);
  $ghUrl       = ($gh['url'] ?? '') ?: 'https://github.com/'.$login;
  $li          = \App\LinkedIn::fetchProfile();
  $liUrl       = (string) ($li['url'] ?? \App\LinkedIn::profileUrl());
  $isHireable  = \App\mh_is_hireable($gh);
  $services    = \App\mh_about_page_services();
  $workTypes   = \App\mh_about_page_work_types();
  $approach    = \App\mh_about_page_approach();
  $jobs        = \App\mh_code_page_resume();
  $skillGroups = \App\mh_code_page_skills_grouped();

  // One stored GitHub snapshot (refreshed hourly by WP-Cron); see app/code-github.php.
  $snap        = \App\mh_code_gh_snapshot();
  $pinned      = $snap['featured'] ?? [];
  $recent      = array_slice($snap['recent'] ?? [], 0, 6);
  $languages   = $snap['languages'] ?? [];
  $syncedAgo   = ! empty($snap['synced_at']) ? \App\mh_github_ago(gmdate('c', (int) $snap['synced_at'])) : '';

  // Now (folded in from /now/ in 3.6.50).
  $nowItems    = \App\field_lines('now_items', \App\mh_now_items_defaults());
  $nowUpdated  = \App\mh_now_updated();
  $posts       = \App\mh_latest_posts(3);
  $journal     = get_permalink((int) get_option('page_for_posts')) ?: home_url('/blog/');

  $needs = [
    __('Who the site or app is for', 'sage'),
    __('What it needs to do', 'sage'),
    __('Any existing sites or references you like', 'sage'),
    __('A rough sense of your timeline', 'sage'),
  ];
@endphp

@section('content')

{{-- HERO --}}
@component('partials.page-hero', ['extra' => 'about-hero'])
  <div class="about-hero__copy">
    <p class="eyebrow">{{ \App\field('about_kicker', __('Matt Hummel', 'sage')) }}</p>
    <h1 class="display-title is-hero">
      {{ \App\field('about_h1', __('WordPress developer for shops and agencies.', 'sage')) }}
    </h1>
    <p class="lead about-hero__lede">
      {{ \App\field('about_lede', __('I build WordPress sites and web apps shops can edit, agencies can hand off, and the next developer can read.', 'sage')) }}
    </p>
    <div class="page-header-split__actions about-hero__actions">
      <a class="btn" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ __('Say hello', 'sage') }}
      </a>
      @include('partials.booking-link')
      <a class="h-text-arrow" href="#hire">{{ __('Hire me', 'sage') }} <span aria-hidden="true">→</span></a>
    </div>
  </div>
@endcomponent

@include('partials.page-nav', [
  'pills' => [
    ['story', __('Story', 'sage')],
    ['now', __('Now', 'sage')],
    ['build', __('What I build', 'sage')],
    ['hire', __('Hire', 'sage')],
    ['resume', __('Resume', 'sage')],
    ['process', __('Process', 'sage')],
    ['code', __('Code', 'sage')],
    ['faq', __('FAQ', 'sage')],
    ['elsewhere', __('Elsewhere', 'sage')],
  ],
])

{{-- STORY --}}
<section class="pf-section about-story-sec" id="story" aria-labelledby="about-story-heading">
  <div class="container wide">
    <div class="about-shell about-shell--story">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner about-story">
        <div class="about-story__copy">
          <p class="eyebrow">{{ __('Story', 'sage') }}</p>
          <h2 id="about-story-heading" class="display-title is-section">
            {{ \App\field('about_story_h2', __('How I got here.', 'sage')) }}
          </h2>
          <div class="about-story__body">
            {!! \App\mh_about_story_html() !!}
          </div>
          <div class="about-story__links">
            <a class="btn" href="{{ home_url('/contact/') }}">
              {!! \App\mh_svg_icon('mail', 16) !!}
              {{ \App\field('about_story_cta', __('Say hello', 'sage')) }}
            </a>
            <a class="about-text-link" href="#now">
              {{ \App\field('about_story_now', __('What I\'m doing now', 'sage')) }} →
            </a>
          </div>
        </div>

        <div class="about-story__aside">
          <div class="about-aside-card">
            <div class="about-aside-card__head">
              <span class="about-aside-card__mark" aria-hidden="true">{!! \App\mh_svg_icon('github', 18) !!}</span>
              <div>
                <p class="about-aside-card__name">
                  <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
                    {{ '@'.$login }}
                    <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
                  </a>
                </p>
                @if (! empty($gh['created']))
                  {{-- translators: %s: year the GitHub account was created --}}
                  <p class="about-aside-card__meta">{{ sprintf(__('On GitHub since %s', 'sage'), $gh['created']) }}</p>
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
                  <span>{{ __('public repos', 'sage') }}</span>
                </li>
              @endif
              @if (! empty($gh['followers']))
                <li>
                  <strong>{{ number_format_i18n($gh['followers']) }}</strong>
                  <span>{{ __('followers', 'sage') }}</span>
                </li>
              @endif
            </ul>
            @if ($isHireable)
              <p class="about-aside-avail">
                @include('partials.avail-mark', ['gh' => $gh])
                {{ \App\mh_availability_label($gh, __('Available for hire', 'sage')) }}
              </p>
            @endif
            <a class="about-aside-card__link" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
              {{ __('View GitHub profile', 'sage') }} →
              <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
            </a>
          </div>

          <div class="about-aside-card about-aside-card--linkedin">
            <div class="about-aside-card__head">
              <span class="about-aside-card__mark" aria-hidden="true">{!! \App\mh_svg_icon('linkedin', 18) !!}</span>
              <div>
                <p class="about-aside-card__name">
                  <a href="{{ esc_url($liUrl) }}" rel="me noopener" target="_blank">
                    {{ $li['name'] ?? 'Matt Hummel' }}
                    <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
                  </a>
                </p>
                @if (! empty($li['headline']))
                  <p class="about-aside-card__meta">{{ $li['headline'] }}</p>
                @endif
              </div>
            </div>
            @if (! empty($li['location']))
              <p class="about-aside-card__bio">{!! \App\mh_svg_icon('map', 13) !!} {{ $li['location'] }}</p>
            @endif
            @if (! empty($li['open_to_work']))
              <p class="about-aside-avail">
                <span class="h-badge__dot" aria-hidden="true"></span>
                {{ __('Open to work', 'sage') }}
              </p>
            @endif
            <a class="about-aside-card__link" href="{{ esc_url($liUrl) }}" rel="me noopener" target="_blank">
              {{ __('View LinkedIn profile', 'sage') }} →
              <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- NOW --}}
<section class="pf-section about-now-sec" id="now" aria-labelledby="about-now-heading">
  <div class="container wide">
    <div class="about-shell about-shell--now">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner about-now">
        <div class="about-now__copy">
          <p class="eyebrow">{{ __('Now', 'sage') }}</p>
          <h2 id="about-now-heading" class="display-title is-section">
            {{ \App\field('now_h1', __('What I’m doing now.', 'sage')) }}
          </h2>
          <p class="sec-intro">{{ \App\field('now_lede', __('A short list of where my time is going.', 'sage')) }}</p>
          <ul class="now-checklist about-now__list">
            @foreach ($nowItems as $item)
              <li>{!! \App\mh_svg_icon('check', 14) !!}<span>{{ $item }}</span></li>
            @endforeach
          </ul>
          <p class="about-now__life">{{ \App\field('now_life_p1', __('I live with my family. Nights and weekends belong to people, not projects. Weekdays I take full-time, contract, and freelance WordPress work. I work Eastern Time hours.', 'sage')) }}</p>
          <p class="about-now__updated">
            {{ __('Last updated', 'sage') }}
            @if ($nowUpdated['iso'] !== '')
              <time datetime="{{ esc_attr($nowUpdated['iso']) }}">{{ $nowUpdated['label'] }}</time>
            @else
              {{ $nowUpdated['label'] }}
            @endif
          </p>
        </div>

        <div class="about-now__posts">
          <h3 class="about-now__posts-title">{!! \App\mh_svg_icon('pen', 16) !!} {{ \App\field('about_posts_h2', __('Recent posts.', 'sage')) }}</h3>
          @if ($posts === [])
            <p class="about-now__empty">{{ \App\field('now_posts_empty', __('No journal posts are published yet.', 'sage')) }}</p>
          @else
            <ol class="now-posts">
              @foreach ($posts as $entry)
                <li>
                  <a href="{{ esc_url((string) ($entry['url'] ?? '')) }}">
                    @if (! empty($entry['date']))
                      <time datetime="{{ esc_attr((string) ($entry['date_iso'] ?? '')) }}">{{ $entry['date'] }}</time>
                    @endif
                    <strong>{{ $entry['title'] }}</strong>
                    @if (! empty($entry['ex']))
                      <span>{{ $entry['ex'] }}</span>
                    @endif
                  </a>
                </li>
              @endforeach
            </ol>
          @endif
          <a class="h-text-arrow" href="{{ esc_url($journal) }}">{{ \App\field('about_posts_all', __('All posts', 'sage')) }} <span aria-hidden="true">→</span></a>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- WHAT I BUILD --}}
<section class="pf-section pf-section--alt about-build-sec" id="build" aria-labelledby="about-services-heading">
  <div class="container wide">
    <div class="about-shell about-shell--build">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner">
        <header class="about-shell__head">
          <p class="eyebrow">{{ __('Services', 'sage') }}</p>
          <h2 id="about-services-heading" class="display-title is-section">
            {{ \App\field('about_services_h2', __('What I build.', 'sage')) }}
          </h2>
          <p class="sec-intro">
            {{ \App\field('about_services_intro', \App\mh_adjacent_range_copy()) }}
          </p>
        </header>
        <div class="about-services">
          @foreach ($services as $i => $svc)
            <article class="about-svc-card">
              <span class="about-svc-card__n" aria-hidden="true">{{ sprintf('%02d', $i + 1) }}</span>
              <div class="about-svc-card__icon">{!! \App\mh_svg_icon($svc['icon'], 22) !!}</div>
              <h3 class="about-svc-card__title">{{ $svc['title'] }}</h3>
              <p class="about-svc-card__body">{{ $svc['body'] }}</p>
            </article>
          @endforeach
        </div>
        <p class="about-services-note">
          {!! \App\field_html('about_services_note', __('Curious about a specific project type? <a href="/contact/">Write a note</a>.', 'sage')) !!}
        </p>
      </div>
    </div>
  </div>
</section>

{{-- HIRE --}}
<section class="pf-section about-work-sec" id="hire" aria-labelledby="about-work-heading">
  <div class="container wide">
    <div class="about-shell about-shell--work">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner about-openwork">
        <div class="about-openwork__copy">
          <p class="eyebrow">{{ __('Hire me', 'sage') }}</p>
          <h2 id="about-work-heading" class="display-title is-section">
            {{ \App\field('about_work_h2', __('Open for work.', 'sage')) }}
          </h2>
          @if ($isHireable)
            <p class="about-aside-avail">
              @include('partials.avail-mark', ['gh' => $gh])
              {{ \App\mh_availability_label($gh, __('Available for hire', 'sage')) }}
            </p>
          @endif
          <p>{{ \App\field('about_work_p1', __('I\'m looking for full-time roles, contract gigs, and freelance projects. Happy to work remote or on-site.', 'sage')) }}</p>
          <p>{{ \App\field('about_work_p2', __('If you’re hiring a full-stack developer, need an experienced WordPress specialist, want agency overflow support, or have a web project to discuss, send a short note about what you’re working on.', 'sage')) }}</p>
          <p class="sec-intro range-note">{{ \App\field('about_work_range', \App\mh_adjacent_range_copy()) }}</p>
          <p class="sec-intro">{{ \App\field('about_work_price', __('Written scope before I start. Custom quotes — no menu of add-ons.', 'sage')) }}</p>
          <div class="about-story__links">
            <a class="btn" href="{{ home_url('/contact/') }}">
              {!! \App\mh_svg_icon('mail', 16) !!}
              {{ \App\field('about_work_cta', __('Start a conversation', 'sage')) }}
            </a>
            <a class="about-text-link" href="{{ esc_url($liUrl) }}" rel="noopener" target="_blank">
              {{ __('LinkedIn', 'sage') }} →
              <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
            </a>
          </div>
        </div>
        <ul class="about-openwork__types">
          @foreach ($workTypes as $type)
            <li class="about-work-type">
              <span class="about-work-type__check" aria-hidden="true">{!! \App\mh_svg_icon('check', 14) !!}</span>
              <div>
                <p class="about-work-type__title">{{ $type['title'] }}</p>
                <p class="about-work-type__detail">{{ $type['detail'] }}</p>
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    </div>

    <div class="hire-need-layout about-need">
      <div class="hire-need-copy">
        <p class="eyebrow">{{ __('To get started', 'sage') }}</p>
        <h3 class="about-need__title">{{ \App\field('about_need_h3', __('What I need from you.', 'sage')) }}</h3>
        <p>{{ \App\field('about_need_intro', __('You don’t need a finished spec. A short description of the problem is enough. Here’s what helps:', 'sage')) }}</p>
        <ul class="hire-need-list">
          @foreach ($needs as $need)
            <li>{{ $need }}</li>
          @endforeach
        </ul>
        <p class="about-need__note">{{ \App\field('about_need_note', __('That’s it. I’ll follow up with clarifying questions or an honest note if I’m not the right fit.', 'sage')) }}</p>
      </div>
      <div class="hire-need-cta">
        <h3 class="hire-need-cta__heading">{{ __('Ready to write?', 'sage') }}</h3>
        <p class="hire-need-cta__body">{{ __('Use the contact form — a paragraph is plenty.', 'sage') }} {{ \App\mh_reply_sla() }}</p>
        <a class="btn hire-need-cta__btn" href="{{ home_url('/contact/') }}">
          {!! \App\mh_svg_icon('mail', 15) !!} {{ __('Say hello', 'sage') }}
        </a>
        @include('partials.booking-link', ['block' => true])
        <p class="hire-need-cta__note">
          {!! sprintf(
            /* translators: %s: LinkedIn profile URL */
            __('Or <a href="%s" rel="noopener" target="_blank">message on LinkedIn</a>', 'sage'),
            esc_url($liUrl)
          ) !!}
        </p>
      </div>
    </div>
  </div>
</section>

{{-- RESUME --}}
@include('partials.resume-timeline', [
  'jobs' => $jobs,
  'linkedin' => $liUrl,
  'headingId' => 'about-resume-heading',
  'h2' => \App\field('hire_cv_h2', __('Resume.', 'sage')),
  'intro' => \App\field('hire_cv_intro', __('Working with shops and agencies anywhere. Open to full-time, contract, and agency overflow. PowerApps, Power Automate, and InfoPath for federal agencies are in the roles below. There is no public demo.', 'sage')),
  'eyebrow' => __('Experience', 'sage'),
])

{{-- At a glance, process, fit --}}
@include('partials.about-hire-sections')

{{-- HOW I WORK --}}
<section class="pf-section pf-section--alt about-approach-sec" id="approach" aria-labelledby="about-approach-heading">
  <div class="container wide">
    <div class="about-shell about-shell--approach">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner">
        <header class="about-shell__head">
          <p class="eyebrow">{{ __('Approach', 'sage') }}</p>
          <h2 id="about-approach-heading" class="display-title is-section">
            {{ \App\field('about_values_h2', __('How I work.', 'sage')) }}
          </h2>
          <p class="sec-intro">
            {{ \App\field('about_values_intro', __('Sage 11, Blade, Tailwind, Vite, and PHP 8.3 — shipped through GitHub. Here is how I run a WordPress build from the first note to a clean handoff.', 'sage')) }}
          </p>
        </header>
        <div class="about-approach">
          @foreach ($approach as $item)
            <article class="about-approach__item">
              <span class="about-approach__icon" aria-hidden="true">{!! \App\mh_svg_icon($item['icon'], 18) !!}</span>
              <div>
                <h3>{{ $item['title'] }}</h3>
                <p>{{ $item['body'] }}</p>
              </div>
            </article>
          @endforeach
        </div>
        <p class="about-services-note">
          <a class="h-text-arrow" href="#code">{{ __('Repos and stack notes below', 'sage') }} →</a>
        </p>
      </div>
    </div>
  </div>
</section>

{{-- CODE --}}
<section class="pf-section code-gh code-repos-sec about-code-sec" id="code" aria-labelledby="about-code-heading">
  <div class="container wide">
    <header class="code-sec-head">
      <div>
        <p class="eyebrow">{{ __('Open source', 'sage') }}</p>
        <h2 id="about-code-heading" class="display-title is-section">
          {{ \App\field('code_gh_h2', __('Open-source full-stack and WordPress code on GitHub.', 'sage')) }}
        </h2>
        <p class="sec-intro">
          {{ \App\field('code_gh_intro', __('Public Sage themes, WordPress plugins, and web apps shops and developers can fork. Stats below pull live from the GitHub API.', 'sage')) }}
        </p>
      </div>
      @if ($syncedAgo !== '')
        <p class="code-sync" title="{{ esc_attr(wp_date('M j, Y g:i a T', (int) $snap['synced_at'])) }}">
          <span class="code-hero-live__dot" aria-hidden="true"></span>
          {{-- translators: %s: relative time, e.g. "2 hours ago" --}}
          {{ sprintf(__('Synced from the GitHub API %s', 'sage'), $syncedAgo) }}
        </p>
      @endif
    </header>

    <dl class="code-pulse">
      <div class="code-pulse__stat">
        <dt>{{ __('Public repos', 'sage') }}</dt>
        <dd>{{ number_format_i18n((int) ($snap['repo_count'] ?? 0)) }}</dd>
      </div>
      @if ((int) ($snap['calendar']['total'] ?? 0) > 0)
        <div class="code-pulse__stat">
          <dt>{{ __('Contributions, last 12 months', 'sage') }}</dt>
          <dd>{{ number_format_i18n((int) $snap['calendar']['total']) }}</dd>
        </div>
      @endif
      @if ((int) ($snap['streaks']['longest'] ?? 0) > 0)
        <div class="code-pulse__stat">
          <dt>{{ __('Current streak', 'sage') }}</dt>
          <dd>
            {{ sprintf(_n('%s day', '%s days', (int) $snap['streaks']['current'], 'sage'), number_format_i18n((int) $snap['streaks']['current'])) }}
            {{-- translators: %s: longest streak in days --}}
            <small>{{ sprintf(__('best %s', 'sage'), number_format_i18n((int) $snap['streaks']['longest'])) }}</small>
          </dd>
        </div>
      @endif
      <div class="code-pulse__stat">
        <dt>{{ __('Stars earned', 'sage') }}</dt>
        <dd>{{ number_format_i18n((int) ($snap['star_total'] ?? 0)) }}</dd>
      </div>
      <div class="code-pulse__stat">
        <dt>{{ __('Followers', 'sage') }}</dt>
        <dd>{{ number_format_i18n((int) ($snap['follower_count'] ?? 0)) }}</dd>
      </div>
    </dl>

    @if ($pinned)
      <div class="code-block" id="gh-featured">
        <header class="code-block__head">
          <div>
            <h3 class="code-block__title">
              {!! \App\mh_svg_icon('thumbtack', 16) !!}
              {{ \App\field('code_pin_h2', __('Pinned on GitHub', 'sage')) }}
            </h3>
            <p class="code-block__intro">{{ \App\field('code_pin_intro', __('The repos pinned to my GitHub profile — the ones I point developers to first. Pin or unpin a repo on GitHub and this list follows within the hour.', 'sage')) }}</p>
          </div>
        </header>
        <ol class="code-repos-grid code-repos-grid--pinned">
          @foreach ($pinned as $i => $r)
            <li class="code-repos-grid__item">
              @include('partials.repo-card', ['r' => $r, 'index' => $i + 1, 'variant' => 'featured'])
            </li>
          @endforeach
        </ol>
      </div>
    @endif

    @if ($recent)
      <div class="code-block" id="gh-updated">
        <header class="code-block__head">
          <div>
            <h3 class="code-block__title">
              {!! \App\mh_svg_icon('git', 16) !!}
              {{ \App\field('code_live_h2', __('Recently pushed', 'sage')) }}
            </h3>
            <p class="code-block__intro">{{ \App\field('code_live_intro', __('Fresh commits on public GitHub repos — a quick read on what I am shipping this week.', 'sage')) }}</p>
          </div>
          <a class="btn btn-outline code-block__cta" href="{{ esc_url($ghUrl.'?tab=repositories') }}" rel="noopener" target="_blank">
            {!! \App\mh_svg_icon('github', 14) !!}
            {{-- translators: %s: number of public repos --}}
            {{ sprintf(__('All %s repos', 'sage'), number_format_i18n((int) ($snap['repo_count'] ?? 0))) }}
            <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
          </a>
        </header>
        <ul class="code-recent">
          @foreach ($recent as $r)
            @php $color = $r['lang'] !== '' ? \App\mh_github_lang_color($r['lang']) : ''; @endphp
            <li class="code-recent__row">
              <a class="code-recent__hit" href="{{ esc_url($r['url']) }}" rel="noopener" target="_blank">
                <span class="code-recent__name">{{ $r['title'] }}</span>
                @if ($r['desc'] !== '')
                  <span class="code-recent__desc">{{ $r['desc'] }}</span>
                @endif
                <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
              </a>
              <span class="code-recent__meta">
                @if ($r['lang'] !== '')
                  <span class="repo-lang"><span class="repo-lang__dot" style="--lang-color: {{ esc_attr($color) }}" aria-hidden="true"></span>{{ \App\mh_title_label($r['lang']) }}</span>
                @endif
                @if ($r['stars'] > 0)
                  <span>{!! \App\mh_svg_icon('star', 12) !!} {{ number_format_i18n($r['stars']) }}</span>
                @endif
                @if ($r['pushed'] !== '')
                  <time datetime="{{ esc_attr($r['pushed']) }}">{{ \App\mh_github_ago($r['pushed']) }}</time>
                @endif
              </span>
            </li>
          @endforeach
        </ul>
      </div>
    @endif

    @if ($languages)
      <section class="code-langs" aria-labelledby="about-langs-heading">
        <div class="code-langs__head">
          <h3 class="code-langs__title" id="about-langs-heading">{!! \App\mh_svg_icon('chart-bar', 16) !!} {{ \App\field('code_lang_h3', __('Languages on GitHub', 'sage')) }}</h3>
          <p class="code-langs__intro">{{ \App\field('code_lang_intro', __('Primary language of each public repo, counted live. Small static sites pull HTML up; the themes and plugins are PHP.', 'sage')) }}</p>
        </div>
        <div class="code-langs__bar" aria-hidden="true">
          @foreach ($languages as $l)
            <span style="--w: {{ (float) $l['pct'] }}%; --c: {{ esc_attr($l['color']) }}"></span>
          @endforeach
        </div>
        <ul class="code-langs__legend">
          @foreach ($languages as $l)
            <li>
              <span class="repo-lang__dot" style="--lang-color: {{ esc_attr($l['color']) }}" aria-hidden="true"></span>
              <span class="code-langs__name">{{ $l['lang'] }}</span>
              <span class="code-langs__pct">{{ number_format_i18n($l['pct'], 0) }}%</span>
              <span class="code-langs__n">{{ sprintf(_n('%s repo', '%s repos', $l['count'], 'sage'), number_format_i18n($l['count'])) }}</span>
            </li>
          @endforeach
        </ul>
      </section>
    @endif
  </div>
</section>

{{-- STACK --}}
<section class="pf-section pf-section--alt code-skills-sec" id="stack" aria-labelledby="about-stack-heading">
  <div class="container wide">
    <div class="code-skills-shell">
      <div class="code-skills-shell__mesh" aria-hidden="true"></div>
      <div class="code-skills-shell__inner">
        <header class="code-skills-shell__head">
          <p class="eyebrow">{{ __('Stack', 'sage') }}</p>
          <h2 id="about-stack-heading" class="display-title is-section">
            {{ \App\field('code_sk_h2', __('Skills and tools.', 'sage')) }}
          </h2>
          <p class="sec-intro">
            {{ \App\field('code_sk_intro', __('WordPress, Sage, Tailwind, and the rest of the stack behind shipped repos. Not an exhaustive list — just what shows up in public GitHub.', 'sage')) }}
          </p>
        </header>

        <div class="code-skills-groups">
          @foreach ($skillGroups as $group)
            <div class="code-skills-group" id="skill-{{ sanitize_title($group['label']) }}">
              <div class="code-skills-group__head">
                <span class="code-skills-group__mark" aria-hidden="true">{!! \App\mh_svg_icon($group['icon'], 16) !!}</span>
                <h3 class="code-skills-group__title">{{ $group['label'] }}</h3>
                <span class="code-skills-group__rule" aria-hidden="true"></span>
                <span class="code-skills-group__count">{{ number_format_i18n(count($group['items'])) }}</span>
              </div>
              <ul class="code-skills-grid">
                @foreach ($group['items'] as $skill)
                  <li class="code-skills__card">
                    {!! \App\mh_skill_tile($skill['name'], $skill['hint']) !!}
                  </li>
                @endforeach
              </ul>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

{{-- FAQ --}}
@include('partials.about-faq')

{{-- ELSEWHERE --}}
<section class="pf-section about-elsewhere-sec" id="elsewhere" aria-labelledby="about-elsewhere-heading">
  <div class="container wide">
    <div class="about-shell about-shell--elsewhere">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner about-elsewhere">
        <div>
          <p class="eyebrow">{{ __('Online', 'sage') }}</p>
          <h2 id="about-elsewhere-heading" class="display-title is-section">
            {{ \App\field('about_elsewhere_h2', __('Where to find me.', 'sage')) }}
          </h2>
          <p class="sec-intro">
            {{ \App\field('about_elsewhere_intro', __('Most of my WordPress code and writing shows up here and on GitHub. RSS is the calmest way to follow along.', 'sage')) }}
          </p>
        </div>
        <div class="about-elsewhere__links">
          @include('partials.social', ['labeled' => true])
        </div>
      </div>
    </div>
  </div>
</section>

{{-- CTA --}}
<section class="cta-band about-cta" aria-labelledby="about-cta-heading" data-reveal>
  <div class="container wide cta-band-inner">
    <div class="cta-band__copy about-cta__copy">
      <p class="eyebrow eyebrow--on-dark">{{ \App\field('about_cta_kicker', __('Get in touch', 'sage')) }}</p>
      <h2 id="about-cta-heading" class="display-title is-section">
        {{ \App\field('about_cta_h2', __('Need a WordPress or full-stack developer?', 'sage')) }}
      </h2>
      <p>{{ \App\field('about_cta_lede', __('Got a question about a post, a project, or a role? Send it over. I usually reply within one business day (ET).', 'sage')) }}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ \App\field('about_cta_btn', __('Say hello', 'sage')) }}
      </a>
      @include('partials.booking-link', ['class' => 'btn btn-ghost'])
      <a class="btn btn-ghost" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
        {!! \App\mh_svg_icon('github', 14) !!} {{ '@'.$login }}
        <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
      </a>
      <p class="cta-band__note">{{ \App\mh_reply_sla('note') }}</p>
    </div>
  </div>
</section>

@endsection
