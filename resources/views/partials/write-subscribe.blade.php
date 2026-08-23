@php
  $writeId = $writeId ?? \App\mh_writing_id();
  $rss = get_feed_link('rss2');
@endphp
<section class="write-subscribe" aria-labelledby="write-sub-h">
  <div>
    <h2 id="write-sub-h" class="display-title is-section">{{ \App\field('write_subscribe_h2', __('Follow with RSS', 'sage'), $writeId) }}</h2>
    <p class="sec-intro">{{ \App\field('write_subscribe_lede', __('There is no email list. Copy the feed URL into Feedly, NetNewsWire, or another reader you already use.', 'sage'), $writeId) }}</p>
  </div>
  <div class="write-subscribe-row">
    <code class="write-rss-url">{{ esc_html($rss) }}</code>
    <button type="button" class="btn" data-copy-rss data-rss="{{ esc_url($rss) }}" aria-live="polite">{{ __('Copy RSS', 'sage') }}</button>
    <a class="btn btn-outline" href="{{ esc_url($rss) }}">{{ __('Open feed', 'sage') }}</a>
  </div>
</section>
