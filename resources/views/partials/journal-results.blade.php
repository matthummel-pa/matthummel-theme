{{--
  Journal results: the post grid and its pagination, or an empty state.
  Shared by the journal home, topic archives, and search so writing-tools.js can
  swap one for another in place (topic pills, pagination, sort, live search).
  The host page keeps its own list class, so archives stay one column beside the aside.
--}}
@php
  $emptyText = $emptyText ?? __('No posts yet.', 'sage');
  $writeUrl = $writeUrl ?? home_url('/blog/');
  $listClass = $listClass ?? 'post-list post-list--roomy';
  $paged = max(1, (int) get_query_var('paged'));
  $pages = max(1, (int) $GLOBALS['wp_query']->max_num_pages);
@endphp
@if (! have_posts())
  <div class="work-empty work-empty--compact">
    <p class="work-empty__text">{{ $emptyText }}</p>
    <div class="work-empty__actions">
      <a class="btn btn-outline" href="{{ esc_url($writeUrl) }}" data-journal-link>{{ __('Show all posts', 'sage') }}</a>
    </div>
  </div>
@else
  <div class="{{ $listClass }}" data-post-list>
    @while (have_posts())
      @php(the_post())
      @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
    @endwhile
  </div>
  @if ($paged < $pages)
    {{-- Scroll-to-load: hidden until writing-tools.js arms it; the page links below stay as the no-JS fallback. --}}
    <div
      class="posts-more"
      data-journal-more
      data-next="{{ esc_url(get_pagenum_link($paged + 1)) }}"
      data-label-loading="{{ esc_attr__('Loading more posts…', 'sage') }}"
      {{-- translators: %d: number of posts just added to the list. --}}
      data-label-added="{{ esc_attr__('Loaded %d more posts.', 'sage') }}"
      data-label-end="{{ esc_attr__('That is every post.', 'sage') }}"
      data-label-error="{{ esc_attr__('Could not load more posts. Try again.', 'sage') }}"
      hidden
    >
      <p class="posts-more__status" role="status" data-journal-more-status></p>
      <button type="button" class="btn btn-outline posts-more__btn" data-journal-more-btn>{{ __('Load more posts', 'sage') }}</button>
    </div>
  @endif
  @if ($pages > 1)
    <div class="posts-nav">
      {{-- translators: 1: current page number, 2: total pages. --}}
      <p class="posts-nav__status">{{ sprintf(__('Page %1$s of %2$s', 'sage'), number_format_i18n($paged), number_format_i18n($pages)) }}</p>
      {!! get_the_posts_pagination([
        'mid_size' => 1,
        'prev_text' => '<span aria-hidden="true">←</span> ' . esc_html__('Previous', 'sage'),
        'next_text' => esc_html__('Next', 'sage') . ' <span aria-hidden="true">→</span>',
        'before_page_number' => '<span class="visually-hidden">' . esc_html__('Page', 'sage') . ' </span>',
        'screen_reader_text' => __('Posts pagination', 'sage'),
        'aria_label' => __('Posts pagination', 'sage'),
      ]) !!}
    </div>
  @endif
@endif
