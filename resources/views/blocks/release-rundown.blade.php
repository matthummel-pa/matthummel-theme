{{-- mh/release-rundown: the next WordPress release, from App\mh_release_watch_data() (app/release-watch.php). --}}
@php
  $rw = \App\mh_release_watch_data();
  $full = ($attributes['variant'] ?? 'summary') === 'full';
  $count = max(0, min(12, (int) ($attributes['count'] ?? 5)));
  $level = max(2, min(4, (int) ($attributes['headingLevel'] ?? 2)));
  $h = 'h'.$level;
  $hSub = 'h'.min(6, $level + 1);
  $kicker = trim((string) ($attributes['kicker'] ?? ''));
  $moreUrl = trim((string) ($attributes['moreUrl'] ?? ''));
  $classes = trim('mhrw-rundown wp-block-mh-release-rundown '.($attributes['className'] ?? ''));

  if ($rw) {
    $days = $rw['release_date'] !== '' ? \App\mh_release_watch_days_until($rw['release_date']) : null;
    $today = wp_date('Y-m-d');
    // The next milestone still ahead is the current step.
    $nextIndex = null;
    foreach ($rw['schedule'] as $i => $row) {
      if ($row['date'] >= $today) { $nextIndex = $i; break; }
    }
    $areas = array_values(array_filter($rw['areas'], fn ($a) => ! empty($a['items'])));
    $total = array_sum(array_map(fn ($a) => count($a['items']), $areas));
    $updates = array_slice($rw['updates'], 0, $count);
  }
@endphp

@if (! $rw)
  @if (defined('REST_REQUEST') && REST_REQUEST)
    {{-- Editor preview only: visitors see nothing until the first check finishes. --}}
    <p class="{{ $classes }}">{{ __('The release data has not been fetched yet. It fills in within a few minutes (or run “wp mh release-watch”).', 'sage') }}</p>
  @endif
@else
  <section class="{{ $classes }}" id="wp-release-rundown" aria-labelledby="mhrw-rundown-title">
    <header class="mhrw-rundown__head">
      <p class="mhrw-rundown__eyebrow">{{ $kicker !== '' ? $kicker : __('Release watch · auto-updated', 'sage') }}</p>
      <{{ $h }} id="mhrw-rundown-title" class="mhrw-rundown__title">
        {{ sprintf(__('What’s coming in WordPress %s', 'sage'), $rw['version']) }}
      </{{ $h }}>
      @if ($rw['release_date'] !== '')
        <p class="mhrw-rundown__meta">
          <span class="mhrw-rundown__date">
            {{ sprintf(__('Scheduled for %s', 'sage'), \App\mh_release_watch_date($rw['release_date'], 'F j, Y')) }}
          </span>
          @if ($days !== null && $days >= 0)
            <span class="mhrw-rundown__pill" data-mhrw-release="{{ $rw['release_date'] }}">
              @if ($days === 0)
                {{ __('Release day', 'sage') }}
              @else
                {{ sprintf(_n('%d day away', '%d days away', $days, 'sage'), $days) }}
              @endif
            </span>
          @endif
        </p>
      @endif
    </header>

    @if (! empty($attributes['showSchedule']) && $rw['schedule'])
      <ol class="mhrw-rundown__schedule" aria-label="{{ __('Release schedule', 'sage') }}">
        @foreach ($rw['schedule'] as $i => $row)
          @php
            $state = $i === $nextIndex ? 'is-next' : ($row['date'] < $today ? 'is-done' : '');
            if ($row['label'] === 'Release') { $state = trim($state.' is-release'); }
          @endphp
          <li class="{{ trim('mhrw-ms '.$state) }}" @if ($i === $nextIndex) aria-current="step" @endif>
            <span class="mhrw-ms__label">{{ $row['label'] }}</span>
            <time class="mhrw-ms__date" datetime="{{ $row['date'] }}">{{ \App\mh_release_watch_date($row['date'], 'M j') }}</time>
          </li>
        @endforeach
      </ol>
    @endif

    @if ($full && $areas)
      <div class="mhrw-features" id="mhrw-features">
        <{{ $hSub }} class="mhrw-rundown__subtitle">{{ sprintf(__('Every planned feature (%d)', 'sage'), $total) }}</{{ $hSub }}>
        <p class="mhrw-features__note">
          {{ __('From the official roadmap. Items ship only if they are ready in time, so some will move to a later release.', 'sage') }}
          @if ($rw['roadmap_url'] !== '')
            <a href="{{ esc_url($rw['roadmap_url']) }}" rel="noopener">{{ __('Read the roadmap', 'sage') }}<span aria-hidden="true"> ↗</span></a>
          @endif
        </p>

        {{-- Plain jump links; resources/js/release-watch.js turns them into filter buttons. --}}
        <nav class="mhrw-features__jump" aria-label="{{ __('Feature areas', 'sage') }}" data-mhrw-jump>
          @foreach ($areas as $area)
            <a href="#mhrw-area-{{ esc_attr($area['id']) }}" data-mhrw-area="{{ esc_attr($area['id']) }}">{{ $area['title'] }} <span>{{ count($area['items']) }}</span></a>
          @endforeach
        </nav>
        <p class="mhrw-sr" aria-live="polite" data-mhrw-status></p>

        <div class="mhrw-areas" data-mhrw-areas>
          @foreach ($areas as $index => $area)
            <details class="mhrw-area" id="mhrw-area-{{ esc_attr($area['id']) }}" @if ($index === 0) open @endif>
              <summary class="mhrw-area__summary">
                <span class="mhrw-area__title">{{ $area['title'] }}</span>
                <span class="mhrw-area__count">{{ sprintf(_n('%d item', '%d items', count($area['items']), 'sage'), count($area['items'])) }}</span>
                <svg class="mhrw-area__chev" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </summary>
              <div class="mhrw-area__body">
                @if ($area['intro'] !== '')
                  <p class="mhrw-area__intro">{{ $area['intro'] }}</p>
                @endif
                <ol class="mhrw-area__list">
                  @foreach ($area['items'] as $item)
                    <li class="mhrw-feature">
                      <p class="mhrw-feature__title">
                        @if ($item['url'] !== '')
                          <a href="{{ esc_url($item['url']) }}" rel="noopener">{{ $item['title'] }}</a>
                        @else
                          {{ $item['title'] }}
                        @endif
                      </p>
                      @if ($item['summary'] !== '')
                        <p class="mhrw-feature__summary">{{ $item['summary'] }}</p>
                      @endif
                    </li>
                  @endforeach
                </ol>
              </div>
            </details>
          @endforeach
        </div>
      </div>
    @endif

    @if ($updates)
      <{{ $hSub }} class="mhrw-rundown__subtitle">{{ __('Latest updates from Make WordPress Core', 'sage') }}</{{ $hSub }}>
      <ol class="mhrw-rundown__list">
        @foreach ($updates as $n => $post)
          <li class="mhrw-change">
            <span class="mhrw-change__num" aria-hidden="true">{{ str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT) }}</span>
            <div class="mhrw-change__body">
              <p class="mhrw-change__meta">
                <span class="mhrw-change__type mhrw-type--{{ esc_attr($post['slug']) }}">{{ $post['type'] }}</span>
                @if ($post['date'] !== '')
                  <time datetime="{{ $post['date'] }}">{{ \App\mh_release_watch_date($post['date'], 'M j, Y') }}</time>
                @endif
              </p>
              <p class="mhrw-change__title"><a href="{{ esc_url($post['url']) }}" rel="noopener">{{ $post['title'] }}</a></p>
              @if ($post['excerpt'] !== '')
                <p class="mhrw-change__excerpt">{{ $post['excerpt'] }}</p>
              @endif
            </div>
          </li>
        @endforeach
      </ol>
    @endif

    @if (! $full && $moreUrl !== '')
      <p class="mhrw-rundown__more">
        <a class="mhrw-rundown__more-link" href="{{ esc_url($moreUrl) }}">{{ __('See every planned feature', 'sage') }}<span aria-hidden="true"> →</span></a>
      </p>
    @endif

    <footer class="mhrw-rundown__foot">
      <p>
        {{ __('Pulled from Make WordPress Core twice a day.', 'sage') }}
        @if (! empty($rw['checked']))
          {{ sprintf(__('Last checked %s.', 'sage'), wp_date('M j, g:i a', (int) $rw['checked'])) }}
        @endif
        @if ($rw['cycle_url'] !== '')
          <a href="{{ esc_url($rw['cycle_url']) }}" rel="noopener">{{ __('Official release page', 'sage') }}<span aria-hidden="true"> ↗</span></a>
        @endif
      </p>
    </footer>
  </section>
@endif
