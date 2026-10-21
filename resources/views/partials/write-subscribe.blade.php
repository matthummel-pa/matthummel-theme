@php
  $writeId = $writeId ?? \App\mh_writing_id();
  $rss = get_feed_link('rss2');
  $hasList = shortcode_exists('mhn_signup');
@endphp
<section class="write-subscribe" aria-labelledby="write-sub-h">
  <div>
    <h2 id="write-sub-h" class="display-title is-section">{{ \App\field('write_subscribe_h2', $hasList ? __('Get new posts by email', 'sage') : __('Follow with RSS', 'sage'), $writeId) }}</h2>
    <p class="sec-intro">{{ \App\field('write_subscribe_lede', $hasList
      ? __('A note when I publish, and nothing else. I keep the address on this site. RSS works too if you already use a reader.', 'sage')
      : __('Copy the feed URL into Feedly, NetNewsWire, or another reader you already use.', 'sage'), $writeId) }}</p>
  </div>
  @if ($hasList)
    <div class="write-subscribe-form">
      {{-- Shortcode HTML is escaped in the plugin. --}}
      {!! do_shortcode('[mhn_signup source="journal"]') !!}
    </div>
  @endif
  <div class="write-subscribe-row">
    <code class="write-rss-url">{{ esc_html($rss) }}</code>
    <button type="button" class="btn" data-copy-rss data-rss="{{ esc_url($rss) }}" aria-live="polite">{{ __('Copy RSS', 'sage') }}</button>
    <a class="btn btn-outline" href="{{ esc_url($rss) }}">{{ __('Open feed', 'sage') }}</a>
  </div>
</section>
