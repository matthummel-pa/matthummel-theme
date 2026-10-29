{{-- matthummel/github-stats: numbers, last push, 90-day heatmap, contribution mix and recent activity, all from the stored GitHub snapshot (refreshed hourly; see app/code-github.php). --}}
@php
  $snap = \App\mh_code_gh_snapshot();
  $syncedAt = (int) ($snap['synced_at'] ?? 0);
  $showSync = ! empty($attributes['showSync']) && $syncedAt > 0;

  $streaks = (array) ($snap['streaks'] ?? []);
  $total12 = (int) ($snap['calendar']['total'] ?? 0);
  $activeDays = (int) ($streaks['active_days'] ?? 0);
  $avgActive = (int) round((float) ($streaks['avg_active'] ?? 0));
  $current = (int) ($streaks['current'] ?? 0);
  $longest = (int) ($streaks['longest'] ?? 0);
  $bestDay = is_array($streaks['best_day'] ?? null) ? $streaks['best_day'] : null;
  $stars = (int) ($snap['star_total'] ?? 0);
  $lastPush = is_array($snap['last_push'] ?? null) ? $snap['last_push'] : null;

  // Heatmap: the snapshot keeps weeks newest-first; draw oldest on the left, and place each day by its weekday so partial weeks line up.
  $heatWeeks = array_reverse((array) ($snap['calendar90']['weeks'] ?? []));
  $heatTotal = (int) ($snap['calendar90']['total'] ?? 0);
  $byDay = (array) ($snap['events_by_day'] ?? []);
  $showHeat = ! empty($attributes['showHeatmap']) && $heatWeeks !== [];
  // Days with a date and days with at least one contribution, for the line under the mix.
  $days90 = 0;
  $active90 = 0;
  foreach ($heatWeeks as $week) {
      foreach ($week as $day) {
          if (($day['date'] ?? '') === '') continue;
          $days90++;
          if ((int) ($day['count'] ?? 0) > 0) $active90++;
      }
  }

  // Mix: what the 12 months of contributions were made of.
  $mixParts = [
      ['key' => 'commits', 'label' => __('Commits', 'sage'), 'n' => (int) ($snap['breakdown']['commits'] ?? 0)],
      ['key' => 'prs', 'label' => __('Pull requests', 'sage'), 'n' => (int) ($snap['breakdown']['prs'] ?? 0)],
      ['key' => 'reviews', 'label' => __('Reviews', 'sage'), 'n' => (int) ($snap['breakdown']['reviews'] ?? 0)],
      ['key' => 'issues', 'label' => __('Issues', 'sage'), 'n' => (int) ($snap['breakdown']['issues'] ?? 0)],
  ];
  $mixTotal = array_sum(array_column($mixParts, 'n'));
  $showMix = ! empty($attributes['showMix']) && $mixTotal > 0;

  $feed = array_slice((array) ($snap['events'] ?? []), 0, 6);
  $showFeed = ! empty($attributes['showActivity']) && $feed !== [];
  $profileUrl = (string) ($snap['url'] ?? ('https://github.com/'.\App\mh_github_login()));
@endphp
<div class="{{ trim('mh-data mh-github-stats gh-stats '.($attributes['className'] ?? '')) }}">
  @if ($showSync)
    <p class="code-sync" title="{{ esc_attr(wp_date('M j, Y g:i a T', $syncedAt)) }}">
      <span class="code-hero-live__dot" aria-hidden="true"></span>
      {{-- translators: %s: relative time, e.g. "2 hours ago" --}}
      {{ sprintf(__('Synced from the GitHub API %s', 'sage'), \App\mh_github_ago(gmdate('c', $syncedAt))) }}
    </p>
  @endif

  <dl class="gh-tiles">
    <div class="gh-tile">
      <span class="gh-tile__icon" aria-hidden="true">{!! \App\mh_svg_icon('github', 20) !!}</span>
      <dt>{{ __('Public repos', 'sage') }}</dt>
      <dd>{{ number_format_i18n((int) ($snap['repo_count'] ?? 0)) }}</dd>
    </div>
    @if ($total12 > 0)
      <div class="gh-tile">
        <span class="gh-tile__icon" aria-hidden="true">{!! \App\mh_svg_icon('code', 20) !!}</span>
        <dt>{{ __('Contributions, last 12 months', 'sage') }}</dt>
        <dd>{{ number_format_i18n($total12) }}</dd>
      </div>
    @endif
    @if ($activeDays > 0)
      <div class="gh-tile">
        <span class="gh-tile__icon" aria-hidden="true">{!! \App\mh_svg_icon('calendar', 20) !!}</span>
        <dt>{{ __('Active days', 'sage') }}</dt>
        <dd>
          {{ number_format_i18n($activeDays) }}
          @if ($avgActive > 0)
            {{-- translators: %s: average contributions on an active day --}}
            <small>{{ sprintf(__('about %s a day', 'sage'), number_format_i18n($avgActive)) }}</small>
          @endif
        </dd>
      </div>
    @endif
    @if ($longest > 0)
      <div class="gh-tile">
        <span class="gh-tile__icon" aria-hidden="true">{!! \App\mh_svg_icon('check', 20) !!}</span>
        <dt>{{ __('Current streak', 'sage') }}</dt>
        <dd>
          {{ sprintf(_n('%s day', '%s days', $current, 'sage'), number_format_i18n($current)) }}
          {{-- translators: %s: longest streak in days --}}
          <small>{{ sprintf(__('best %s', 'sage'), number_format_i18n($longest)) }}</small>
        </dd>
      </div>
    @endif
    <div class="gh-tile">
      <span class="gh-tile__icon" aria-hidden="true">{!! \App\mh_svg_icon('star', 20) !!}</span>
      <dt>{{ __('Stars earned', 'sage') }}</dt>
      <dd>{{ number_format_i18n($stars) }}</dd>
    </div>
    <div class="gh-tile">
      <span class="gh-tile__icon" aria-hidden="true">{!! \App\mh_svg_icon('users', 20) !!}</span>
      <dt>{{ __('Followers', 'sage') }}</dt>
      <dd>{{ number_format_i18n((int) ($snap['follower_count'] ?? 0)) }}</dd>
    </div>
  </dl>

  @if ($lastPush && ($lastPush['when'] ?? '') !== '')
    <p class="gh-lastpush">
      <span class="gh-lastpush__dot" aria-hidden="true"></span>
      {{ __('Last push to', 'sage') }}
      <a href="{{ esc_url($lastPush['url']) }}" rel="noopener" target="_blank">{{ \App\mh_title_label($lastPush['repo']) }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>,
      <time datetime="{{ esc_attr($lastPush['when']) }}">{{ \App\mh_github_ago($lastPush['when']) }}</time>
    </p>
  @endif

  @if ($showHeat || $showMix)
  <div class="gh-pair">
  @if ($showHeat)
    @php
      $heatSummary = sprintf(
          /* translators: %s: number of contributions in the last 90 days */
          _n('%s contribution in the last 90 days.', '%s contributions in the last 90 days.', $heatTotal, 'sage'),
          number_format_i18n($heatTotal)
      );
      if ($bestDay && (int) ($bestDay['count'] ?? 0) > 0) {
          $bestTs = strtotime(((string) $bestDay['date']).' UTC');
          $heatSummary .= ' '.sprintf(
              /* translators: 1: contribution count, 2: date */
              __('Busiest day in 12 months: %1$s contributions on %2$s.', 'sage'),
              number_format_i18n((int) $bestDay['count']),
              $bestTs ? date_i18n('M j', $bestTs) : (string) $bestDay['date']
          );
      }
    @endphp
    <figure class="gh-panel gh-heat">
      <figcaption class="gh-panel__head">
        <h3 class="gh-panel__title">{{ __('Last 90 days', 'sage') }}</h3>
        <p class="gh-panel__note">{{ $heatSummary }}</p>
      </figcaption>
      <div class="gh-heat__scroll">
        <div class="gh-heat__grid" style="--weeks: {{ count($heatWeeks) }}" role="img" aria-label="{{ esc_attr($heatSummary) }}">
          @foreach ($heatWeeks as $wi => $week)
            @foreach ($week as $day)
              @continue(($day['date'] ?? '') === '')
              @php
                $dow = (int) gmdate('w', (int) strtotime(((string) $day['date']).' UTC'));
                $tip = \App\mh_github_day_tip((string) $day['date'], (int) $day['count'], (array) ($byDay[$day['date']] ?? []));
              @endphp
              <span class="gh-heat__day" data-level="{{ (int) $day['level'] }}" style="grid-column: {{ $wi + 1 }}; grid-row: {{ $dow + 1 }}" title="{{ esc_attr($tip) }}"></span>
            @endforeach
          @endforeach
        </div>
      </div>
      <p class="gh-heat__legend" aria-hidden="true">
        <span>{{ __('Less', 'sage') }}</span>
        @foreach ([0, 1, 2, 3, 4] as $lv)
          <span class="gh-heat__day" data-level="{{ $lv }}"></span>
        @endforeach
        <span>{{ __('More', 'sage') }}</span>
      </p>
    </figure>
  @endif

      @if ($showMix)
        <section class="gh-panel gh-mix" aria-labelledby="gh-mix-title">
          <div class="gh-panel__head">
            <h3 class="gh-panel__title" id="gh-mix-title">{{ __('What the contributions are', 'sage') }}</h3>
            <p class="gh-panel__note">{{ __('Last 12 months', 'sage') }}</p>
          </div>
          <div class="gh-mix__bar" aria-hidden="true">
            @foreach ($mixParts as $part)
              @continue($part['n'] <= 0)
              <span class="gh-mix__seg gh-mix__seg--{{ $part['key'] }}" style="--n: {{ $part['n'] }}"></span>
            @endforeach
          </div>
          <ul class="gh-mix__legend">
            @foreach ($mixParts as $part)
              <li>
                <span class="gh-mix__dot gh-mix__seg--{{ $part['key'] }}" aria-hidden="true"></span>
                <span class="gh-mix__name">{{ $part['label'] }}</span>
                <span class="gh-mix__n">{{ number_format_i18n($part['n']) }}</span>
                <span class="gh-mix__pct">{{ $part['n'] > 0 ? ($part['n'] / $mixTotal * 100 < 1 ? '<1%' : number_format_i18n($part['n'] / $mixTotal * 100, 0).'%') : '0%' }}</span>
              </li>
            @endforeach
          </ul>
          @if ($days90 > 0)
            <div class="gh-mix__foot">
              <p>{{ sprintf(
                  /* translators: 1: days with contributions, 2: days in the window */
                  __('Active on %1$s of the last %2$s days.', 'sage'),
                  number_format_i18n($active90),
                  number_format_i18n($days90)
              ) }}</p>
              @if ($bestDay && (int) ($bestDay['count'] ?? 0) > 0)
                @php $bestTs2 = strtotime(((string) $bestDay['date']).' UTC'); @endphp
                <p>{{ sprintf(
                    /* translators: 1: contribution count, 2: date */
                    __('Busiest day in 12 months: %1$s contributions on %2$s.', 'sage'),
                    number_format_i18n((int) $bestDay['count']),
                    $bestTs2 ? date_i18n('M j', $bestTs2) : (string) $bestDay['date']
                ) }}</p>
              @endif
            </div>
          @endif
        </section>
      @endif
  </div>
  @endif

  @if ($showFeed)
        <section class="gh-panel gh-feed" aria-labelledby="gh-feed-title">
          <div class="gh-panel__head">
            <h3 class="gh-panel__title" id="gh-feed-title">{{ __('Recent activity', 'sage') }}</h3>
            <a class="gh-panel__link" href="{{ esc_url($profileUrl) }}" rel="noopener" target="_blank">{{ __('On GitHub', 'sage') }} →<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
          </div>
          <ol class="gh-feed__list">
            @foreach ($feed as $ev)
              <li class="gh-feed__item">
                <span class="gh-feed__icon" aria-hidden="true">{!! \App\mh_svg_icon(\App\mh_github_event_icon((string) ($ev['type'] ?? '')), 14) !!}</span>
                @if (($ev['url'] ?? '') !== '')
                  <a class="gh-feed__text" href="{{ esc_url($ev['url']) }}" rel="noopener" target="_blank">{{ $ev['text'] }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
                @else
                  <span class="gh-feed__text">{{ $ev['text'] }}</span>
                @endif
                @if (($ev['when'] ?? '') !== '')
                  <time class="gh-feed__when" datetime="{{ esc_attr($ev['when']) }}">{{ \App\mh_github_ago($ev['when']) }}</time>
                @endif
              </li>
            @endforeach
          </ol>
        </section>
  @endif
</div>
