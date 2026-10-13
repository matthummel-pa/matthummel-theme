{{--
  matthummel/journal-list: the Journal's toolbar, topic pills and post list from the page's own query.
  resources/js/writing-tools.js swaps the data-journal-region parts in place (topics, search, sort, page links).
  Anywhere else (the editor preview, another page) there is no post query to print, so it shows a note or nothing.
--}}
@php
  $wrapper = trim('mh-data mh-journal-list '.($attributes['className'] ?? ''));
  $isJournal = is_home() && ! (defined('REST_REQUEST') && REST_REQUEST);
@endphp
@if (! $isJournal)
  @if ((defined('REST_REQUEST') && REST_REQUEST) || is_admin())
    <div class="{{ $wrapper }}">
      <p class="h-journal__empty">{{ __('Journal posts show here on the live page: search, topics, sort, and the post list.', 'sage') }}</p>
    </div>
  @endif
@else
  @php
    $writeId = \App\mh_writing_id();
    $writeUrl = $writeId ? (string) get_permalink($writeId) : home_url('/blog/');
    $emptyText = trim((string) ($attributes['emptyText'] ?? ''));
    $searchPlaceholder = trim((string) ($attributes['searchPlaceholder'] ?? ''));
    $pageBefore = $GLOBALS['post'] ?? null;
  @endphp
  <div class="{{ $wrapper }} write-hub write-hub--home" data-journal data-journal-home="{{ esc_url($writeUrl) }}" data-journal-list="post-list post-list--roomy">
    @include('partials.write-toolbar', ['writeId' => $writeId, 'writeUrl' => $writeUrl, 'searchPlaceholder' => $searchPlaceholder])
    <div data-journal-region="topics">
      @include('partials.write-topics', compact('writeId', 'writeUrl'))
    </div>
    <div data-journal-region="results">
      @include('partials.journal-results', ['writeUrl' => $writeUrl, 'emptyText' => $emptyText !== '' ? $emptyText : __('No posts yet.', 'sage')])
    </div>
  </div>
  @php
    // The post loop moved the global post; later blocks on the page read the page again.
    rewind_posts();
    if ($pageBefore instanceof \WP_Post) {
      $GLOBALS['post'] = $pageBefore;
      setup_postdata($pageBefore);
    }
  @endphp
@endif
