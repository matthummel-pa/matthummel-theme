{{-- matthummel/rss-feed: the feed address, a copy button (resources/js/writing-tools.js) and a link to the feed. --}}
@php
  $rss = (string) get_feed_link('rss2');
  $copyLabel = trim((string) ($attributes['copyLabel'] ?? ''));
  $openLabel = trim((string) ($attributes['openLabel'] ?? ''));
@endphp
<div class="{{ trim('mh-data mh-rss-feed journal-subscribe '.($attributes['className'] ?? '')) }}">
  <div class="write-subscribe-row">
    <code class="write-rss-url">{{ $rss }}</code>
    <button type="button" class="btn" data-copy-rss data-rss="{{ esc_url($rss) }}" aria-live="polite">{{ $copyLabel !== '' ? $copyLabel : __('Copy RSS', 'sage') }}</button>
    @if ($openLabel !== '')
      <a class="btn btn-outline" href="{{ esc_url($rss) }}">{{ $openLabel }}</a>
    @endif
  </div>
</div>
