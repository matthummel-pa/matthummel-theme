{{-- matthummel/github-stats: numbers from the stored GitHub snapshot (refreshed hourly; see app/code-github.php). --}}
@php
  $snap = \App\mh_code_gh_snapshot();
  $syncedAt = (int) ($snap['synced_at'] ?? 0);
  $showSync = ! empty($attributes['showSync']) && $syncedAt > 0;
@endphp
<div class="{{ trim('mh-data mh-github-stats '.($attributes['className'] ?? '')) }}">
  @if ($showSync)
    <p class="code-sync" title="{{ esc_attr(wp_date('M j, Y g:i a T', $syncedAt)) }}">
      <span class="code-hero-live__dot" aria-hidden="true"></span>
      {{-- translators: %s: relative time, e.g. "2 hours ago" --}}
      {{ sprintf(__('Synced from the GitHub API %s', 'sage'), \App\mh_github_ago(gmdate('c', $syncedAt))) }}
    </p>
  @endif
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
</div>
