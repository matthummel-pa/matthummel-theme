{{-- matthummel/github-activity: recent public GitHub activity across every repo (App\Github::fetchEvents, cached, fails soft), plus the latest release of each product repo in the catalog. --}}
@php
  $count = min(40, max(1, (int) ($attributes['count'] ?? 20)));
  $login = \App\mh_github_login();
  $profile = 'https://github.com/'.$login;
  $items = \App\mh_github_activity($count);
  $releases = ! empty($attributes['showReleases']) ? \App\mh_product_latest_releases() : [];
@endphp
<div class="{{ trim('mh-data mh-github-activity '.($attributes['className'] ?? '')) }}">
  @if ($releases !== [])
    <h3 class="cl-sub">{{ __('Latest releases', 'sage') }}</h3>
    <ul class="code-recent">
      @foreach ($releases as $r)
        <li class="code-recent__row">
          <a class="code-recent__hit" href="{{ esc_url($r['url']) }}" rel="noopener" target="_blank">
            <span class="code-recent__name">{{ $r['name'] }} {{ $r['tag'] }}</span>
            <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
          </a>
          @if ($r['date'] !== '')
            <span class="code-recent__meta">{{ $r['date'] }}</span>
          @endif
        </li>
      @endforeach
    </ul>
  @endif

  <h3 class="cl-sub">{{ __('Recent activity', 'sage') }}</h3>
  @if ($items === [])
    <p class="h-journal__empty">
      {{ __('GitHub is not answering right now.', 'sage') }}
      <a href="{{ esc_url($profile) }}" rel="noopener" target="_blank">{{ __('See my activity on GitHub', 'sage') }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
    </p>
  @else
    <ul class="code-recent">
      @foreach ($items as $e)
        <li class="code-recent__row">
          <a class="code-recent__hit" href="{{ esc_url($e['url']) }}" rel="noopener" target="_blank">
            <span class="code-recent__name">{{ $e['label'] }}</span>
            <span class="code-recent__desc">{{ preg_replace('#^[^/]+/#', '', (string) $e['repo']) }}</span>
            <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
          </a>
          @if (($e['when'] ?? '') !== '')
            <span class="code-recent__meta"><time datetime="{{ esc_attr($e['when']) }}">{{ \App\mh_github_ago($e['when']) }}</time></span>
          @endif
        </li>
      @endforeach
    </ul>
    <p class="cl-entry__more">
      <a href="{{ esc_url($profile) }}" rel="noopener" target="_blank">{{ __('All of my activity is on GitHub', 'sage') }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
    </p>
  @endif
</div>
