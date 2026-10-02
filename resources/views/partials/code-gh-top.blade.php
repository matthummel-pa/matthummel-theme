{{-- Code page: hero, live pulse, pinned + recent repos, shipping activity. Data: \App\mh_code_gh_snapshot(). --}}
@php
  $profile = $gh['profile'];
  $login = $gh['login'];
  $ghUrl = $gh['url'];
  $streaks = $gh['streaks'];
  $breakdown = $gh['breakdown'];
  $yearTotal = (int) ($gh['calendar']['total'] ?? 0);
  $lastPush = $gh['last_push'];
  $cal = $gh['calendar90'];
  $weeks = $cal['weeks'] ?? [];
  $calMonths = \App\mh_github_calendar_months($weeks);
  $dayIndex = 0;
  $syncedAgo = $gh['synced_at'] > 0 ? \App\mh_github_ago(gmdate('c', $gh['synced_at'])) : '';
  $bestDay = $streaks['best_day'] ?? null;
@endphp

{{-- HERO --}}
@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('code_kicker', __('Code', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('code_h1', __('Code and repos.', 'sage')) }}
  </h1>
  <p class="lead">
    {!! \App\field_html('code_lede', __('Public GitHub work — themes, plugins, and apps you can fork or read. This is where the stack detail lives.', 'sage')) !!}
  </p>
  <div class="page-header-split__actions">
    <a class="btn" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
      {!! \App\mh_svg_icon('github', 16) !!} {{ __('View GitHub', 'sage') }}
      <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
    </a>
    <a class="h-text-arrow" href="{{ home_url('/hire/') }}">
      {{ __('Hire me', 'sage') }} <span aria-hidden="true">→</span>
    </a>
  </div>
  @if ($lastPush && $lastPush['when'] !== '')
    <p class="code-hero-live">
      <span class="code-hero-live__dot" aria-hidden="true"></span>
      {{ __('Last push', 'sage') }}
      <time datetime="{{ esc_attr($lastPush['when']) }}">{{ \App\mh_github_ago($lastPush['when']) }}</time>
      {{ __('to', 'sage') }}
      <a href="{{ esc_url($lastPush['url']) }}" rel="noopener" target="_blank">{{ \App\mh_title_label($lastPush['repo']) }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
    </p>
  @endif
@endcomponent

@include('partials.page-nav', [
  'pills' => [
    ['repos', __('Repos', 'sage')],
    ['activity', __('Activity', 'sage')],
    ['skills', __('Stack', 'sage')],
    ['practice', __('Practice', 'sage')],
    ['community', __('Community', 'sage')],
    ['docs', __('Docs', 'sage')],
  ],
])

{{-- REPOS --}}
<section class="pf-section code-gh code-repos-sec" id="repos" aria-labelledby="code-gh-heading">
  <div class="container wide">
    <header class="code-sec-head">
      <div>
        <p class="eyebrow">{{ __('Open source', 'sage') }}</p>
        <h2 id="code-gh-heading" class="display-title is-section">
          {{ \App\field('code_gh_h2', __('Open-source full-stack and WordPress code on GitHub.', 'sage')) }}
        </h2>
        <p class="sec-intro">
          {{ \App\field('code_gh_intro', __('Public Sage themes, WordPress plugins, and web apps shops and developers can fork. Stats and activity below pull live from the GitHub API.', 'sage')) }}
        </p>
      </div>
      @if ($syncedAgo !== '')
        <p class="code-sync" title="{{ esc_attr(wp_date('M j, Y g:i a T', $gh['synced_at'])) }}">
          <span class="code-hero-live__dot" aria-hidden="true"></span>
          {{ sprintf(__('Synced from the GitHub API %s', 'sage'), $syncedAgo) }}
        </p>
      @endif
    </header>

    <dl class="code-pulse">
      <div class="code-pulse__stat">
        <dt>{{ __('Public repos', 'sage') }}</dt>
        <dd>{{ number_format_i18n($gh['repo_count']) }}</dd>
      </div>
      @if ($yearTotal > 0)
        <div class="code-pulse__stat">
          <dt>{{ __('Contributions, last 12 months', 'sage') }}</dt>
          <dd>{{ number_format_i18n($yearTotal) }}</dd>
        </div>
      @endif
      @if ($streaks['longest'] > 0)
        <div class="code-pulse__stat">
          <dt>{{ __('Current streak', 'sage') }}</dt>
          <dd>
            {{ sprintf(_n('%s day', '%s days', $streaks['current'], 'sage'), number_format_i18n($streaks['current'])) }}
            <small>{{ sprintf(__('best %s', 'sage'), number_format_i18n($streaks['longest'])) }}</small>
          </dd>
        </div>
      @endif
      <div class="code-pulse__stat">
        <dt>{{ __('Stars earned', 'sage') }}</dt>
        <dd>{{ number_format_i18n($gh['star_total']) }}</dd>
      </div>
      <div class="code-pulse__stat">
        <dt>{{ __('Followers', 'sage') }}</dt>
        <dd>{{ number_format_i18n($gh['follower_count']) }}</dd>
      </div>
    </dl>

    @if ($gh['featured'])
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
          @foreach ($gh['featured'] as $i => $r)
            <li class="code-repos-grid__item">
              @include('partials.repo-card', ['r' => $r, 'index' => $i + 1, 'variant' => 'featured'])
            </li>
          @endforeach
        </ol>
      </div>
    @endif

    @if ($gh['recent'])
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
            {{ sprintf(__('All %s repos', 'sage'), number_format_i18n($gh['repo_count'])) }}
            <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
          </a>
        </header>
        <ul class="code-recent">
          @foreach ($gh['recent'] as $r)
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
  </div>
</section>

{{-- ACTIVITY --}}
@if ($weeks || $gh['events'])
<section class="pf-section pf-section--alt code-gh code-activity-sec" id="activity" aria-labelledby="code-act-heading">
  <div class="container wide">
    <header class="code-sec-head">
      <div>
        <p class="eyebrow">{{ __('Activity', 'sage') }}</p>
        <h2 id="code-act-heading" class="display-title is-section">{{ \App\field('code_act_sec_h2', __('Shipping activity.', 'sage')) }}</h2>
        <p class="sec-intro">{{ \App\field('code_act_sec_intro', __('Every number here is counted by GitHub, not by me: commits, pull requests, and the days I shipped something public.', 'sage')) }}</p>
      </div>
    </header>

    @if ($breakdown || $streaks['active_days'] > 0)
      <ul class="code-breakdown" aria-label="{{ __('Contributions in the last 12 months', 'sage') }}">
        @if (! empty($breakdown['commits']))
          <li><strong>{{ number_format_i18n($breakdown['commits']) }}</strong> {{ _n('commit', 'commits', $breakdown['commits'], 'sage') }}</li>
        @endif
        @if (! empty($breakdown['prs']))
          <li><strong>{{ number_format_i18n($breakdown['prs']) }}</strong> {{ _n('pull request', 'pull requests', $breakdown['prs'], 'sage') }}</li>
        @endif
        @if (! empty($breakdown['repos']))
          <li><strong>{{ number_format_i18n($breakdown['repos']) }}</strong> {{ _n('repo contributed to', 'repos contributed to', $breakdown['repos'], 'sage') }}</li>
        @endif
        @if ($streaks['active_days'] > 0)
          <li><strong>{{ number_format_i18n($streaks['active_days']) }}</strong> {{ _n('active day', 'active days', $streaks['active_days'], 'sage') }}</li>
        @endif
        @if ($bestDay)
          <li>
            <strong>{{ number_format_i18n($bestDay['count']) }}</strong>
            {{ sprintf(__('on my busiest day (%s)', 'sage'), wp_date('M j', strtotime($bestDay['date'].' 12:00:00 UTC'))) }}
          </li>
        @endif
      </ul>
    @endif

    <div class="code-gh-split">
      @if ($weeks)
      <div class="code-gh-panel code-gh-cal" id="gh-contributions" style="--gh-weeks: {{ max(1, count($weeks)) }}">
        <div class="code-gh-panel__head">
          <span class="code-gh-panel__mark" aria-hidden="true">{!! \App\mh_svg_icon('calendar', 18) !!}</span>
          <div>
            <h3 class="code-gh-panel__title">{{ \App\field('code_cal_h2', __('Last 90 days of commits', 'sage')) }}</h3>
            <p class="code-gh-panel__intro">
              {{ \App\field('code_cal_intro', __('Contribution heat map for the last 90 days, newest week first. Hover a day to see what shipped. Darker blue means a busier day on public repos.', 'sage')) }}
              @if ((int) $cal['total'] > 0)
                <strong>{{ sprintf(__('%s contributions in the last %s days.', 'sage'), number_format_i18n((int) $cal['total']), number_format_i18n((int) $cal['days'])) }}</strong>
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
                    $date = (string) ($day['date'] ?? '');
                    $tip = $date !== '' ? \App\mh_github_day_tip($date, (int) ($day['count'] ?? 0), $gh['events_by_day'][$date] ?? []) : '';
                    $i = $dayIndex++;
                  @endphp
                  @if ($date !== '')
                    <button type="button" class="gh-day" data-level="{{ (int) ($day['level'] ?? 0) }}" style="--i: {{ $i }}" aria-label="{{ esc_attr($tip) }}">
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

      @if ($gh['events'])
      <div class="code-gh-panel code-gh-activity" id="gh-activity">
        <div class="code-gh-panel__head">
          <span class="code-gh-panel__mark" aria-hidden="true">{!! \App\mh_svg_icon('code', 18) !!}</span>
          <div>
            <h3 class="code-gh-panel__title">{{ \App\field('code_act_h2', __('Public activity', 'sage')) }}</h3>
            <p class="code-gh-panel__intro">{{ \App\field('code_act_intro', __('Pushes, releases, and pull requests from the last 90 days — newest first. Open any row to jump into the repo.', 'sage')) }}</p>
          </div>
        </div>
        <ol class="code-gh-feed">
          @foreach ($gh['events'] as $ev)
            @php
              $evType = (string) ($ev['type'] ?? '');
              $evRepo = (string) ($ev['repo'] ?? '');
            @endphp
            <li class="code-gh-feed__item" data-type="{{ esc_attr($evType) }}">
              <span class="code-gh-feed__icon" aria-hidden="true">{!! \App\mh_svg_icon(\App\mh_github_event_icon($evType), 14) !!}</span>
              <div class="code-gh-feed__body">
                <a class="code-gh-feed__link" href="{{ esc_url($ev['url']) }}" rel="noopener" target="_blank">
                  {{ $ev['label'] ?? $ev['text'] }}
                  <span class="visually-hidden"> {{ $evRepo }} {{ __('(opens in a new window)', 'sage') }}</span>
                </a>
                @if ($evRepo !== '')
                  <span class="code-gh-feed__repo">{!! \App\mh_svg_icon('github', 12) !!} {{ preg_replace('#^[^/]+/#', '', $evRepo) }}</span>
                @endif
              </div>
              @if (! empty($ev['when']))
                <time datetime="{{ esc_attr($ev['when']) }}">{{ \App\mh_github_ago($ev['when']) }}</time>
              @endif
            </li>
          @endforeach
        </ol>
        <a class="code-gh-community__link" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
          {!! \App\mh_svg_icon('github', 14) !!}
          {{ __('Full activity on GitHub', 'sage') }}
          <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
        </a>
      </div>
      @endif
    </div>
  </div>
</section>
@endif
