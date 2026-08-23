@php
  $writeId = $writeId ?? \App\mh_writing_id();
  $writeUrl = $writeUrl ?? ($writeId ? get_permalink($writeId) : home_url('/blog/'));
  $hideSearch = ! empty($hideSearch);
  $rss = get_feed_link('rss2');
  $found = (int) $GLOBALS['wp_query']->found_posts;
  $total = \App\mh_published_post_count();
  $oldest = \App\mh_journal_is_oldest();
  if (is_search()) {
    $countLabel = sprintf(_n('%d result', '%d results', $found, 'sage'), $found);
  } elseif (is_category() || is_tag() || is_date()) {
    $countLabel = sprintf(_n('%d post', '%d posts', $found, 'sage'), $found);
  } else {
    $countLabel = sprintf(_n('%d post', '%d posts', $total, 'sage'), $total);
  }
@endphp
<div class="write-tools" data-write-tools>
  @if (! $hideSearch)
    <div class="search-wrap search-wrap--inline">
      @include('forms.search', ['placeholder' => \App\field('write_search_ph', __('Search posts', 'sage'), $writeId)])
    </div>
  @endif
  <div class="write-tool-actions">
    <p class="write-count">{{ $countLabel }}</p>
    <div class="write-sort" role="group" aria-label="{{ __('Sort posts', 'sage') }}">
      <a class="write-sort-btn{{ $oldest ? '' : ' is-active' }}" href="{{ esc_url(\App\mh_journal_sort_url('newest')) }}" @if (! $oldest) aria-current="page" @endif>{{ __('Newest', 'sage') }}</a>
      <a class="write-sort-btn{{ $oldest ? ' is-active' : '' }}" href="{{ esc_url(\App\mh_journal_sort_url('oldest')) }}" @if ($oldest) aria-current="page" @endif>{{ __('Oldest', 'sage') }}</a>
    </div>
    <a class="write-tool-link" href="{{ esc_url($rss) }}">{{ __('RSS', 'sage') }}</a>
    <button type="button" class="write-tool-link" data-copy-rss data-rss="{{ esc_url($rss) }}">{{ __('Copy feed', 'sage') }}</button>
    <div class="write-view" role="group" aria-label="{{ __('Layout', 'sage') }}">
      <button type="button" class="write-view-btn is-active" data-write-view="grid" aria-pressed="true">{{ __('Grid', 'sage') }}</button>
      <button type="button" class="write-view-btn" data-write-view="list" aria-pressed="false">{{ __('List', 'sage') }}</button>
    </div>
    <p class="write-kbd"><kbd>/</kbd> {{ __('to search', 'sage') }}</p>
  </div>
</div>
