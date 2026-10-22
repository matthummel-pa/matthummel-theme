{{-- matthummel/release-banner: countdown bar to the next WordPress release. Data: app/release-watch.php. --}}
@php
  $rw = \App\mh_release_watch_data();
  $days = ($rw && $rw['release_date'] !== '') ? \App\mh_release_watch_days_until($rw['release_date']) : null;
  $isEditor = defined('REST_REQUEST') && REST_REQUEST;
  $ctaText = trim((string) ($attributes['ctaText'] ?? ''));
  $ctaUrl = trim((string) ($attributes['ctaUrl'] ?? ''));
  $dismissible = ! empty($attributes['dismissible']);
  // One key per release, so closing it this cycle does not hide the next one.
  $key = $rw ? 'mhrw:countdown:'.$rw['version'] : '';
@endphp

@if ($days === null || $days < 0)
  @if ($isEditor)
    <p class="mhrw-banner-empty">{{ $rw ? __('The release has shipped. The banner comes back when the next release has a date.', 'sage') : __('The release data has not been fetched yet. It fills in within a few minutes.', 'sage') }}</p>
  @endif
@else
  <aside class="{{ trim('mhrw-banner mhrw-banner--countdown '.($attributes['className'] ?? '')) }}" aria-label="{{ __('WordPress release countdown', 'sage') }}" data-release="{{ $rw['release_date'] }}" @if ($dismissible) data-key="{{ $key }}" @endif>
    <div class="mhrw-banner__inner">
      <span class="mhrw-banner__eyebrow">{{ __('Release watch', 'sage') }}</span>
      <p class="mhrw-banner__text">
        <strong class="mhrw-banner__lead">{{ sprintf(__('WordPress %1$s lands %2$s', 'sage'), $rw['version'], \App\mh_release_watch_date($rw['release_date'], 'M j')) }}</strong>
        <span class="mhrw-banner__sep" aria-hidden="true">·</span>
        <span class="mhrw-banner__days" data-mhrw-days>
          @if ($days === 0)
            {{ __('Release day', 'sage') }}
          @else
            {{ sprintf(_n('%d day to go', '%d days to go', $days, 'sage'), $days) }}
          @endif
        </span>
      </p>
      @if ($ctaText !== '' && $ctaUrl !== '')
        <a class="mhrw-banner__cta" href="{{ esc_url($ctaUrl) }}">{{ $ctaText }}<span aria-hidden="true"> →</span></a>
      @endif
      @if ($dismissible && ! $isEditor)
        <button type="button" class="mhrw-banner__close" data-mhrw-dismiss aria-label="{{ __('Dismiss release banner', 'sage') }}">
          <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      @endif
    </div>
  </aside>
  @if ($dismissible && ! $isEditor)
    {{-- Runs before paint so a closed banner never flashes. The key is built from the version only. --}}
    <script>try{var b=document.currentScript.previousElementSibling;if(b&&localStorage.getItem(b.dataset.key)==='1'){b.hidden=true}}catch(e){}</script>
  @endif
@endif
