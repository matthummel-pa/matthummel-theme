@php
  $postId = $postId ?? (int) get_the_ID();
  $summary = $summary ?? '';
  $toc = $toc ?? [];
  $popular = \App\mh_popular_posts(5, $postId);
  $cats = get_categories(['hide_empty' => true, 'number' => 12]);
@endphp
<aside class="post-sidebar" aria-label="{{ __('Post sidebar', 'sage') }}">
  @if ($summary !== '')
    <section class="side-card" aria-labelledby="side-summary-h">
      <h2 id="side-summary-h" class="side-card-title">{{ __('Summary', 'sage') }}</h2>
      <p class="side-summary">{{ $summary }}</p>
    </section>
  @endif

  @if ($toc)
    <nav class="side-card side-card--toc" aria-labelledby="side-toc-h">
      <h2 id="side-toc-h" class="side-card-title">{{ __('On this page', 'sage') }}</h2>
      <ol class="side-toc">
        @foreach ($toc as $item)
          <li class="side-toc-h{{ $item['level'] }}">
            <a href="#{{ esc_attr($item['id']) }}">{{ $item['text'] }}</a>
          </li>
        @endforeach
      </ol>
    </nav>
  @endif

  <section class="side-card" aria-labelledby="side-search-h">
    <h2 id="side-search-h" class="side-card-title">{{ __('Search', 'sage') }}</h2>
    {!! get_search_form(false) !!}
  </section>

  @if ($popular)
    <section class="side-card" aria-labelledby="side-pop-h">
      <h2 id="side-pop-h" class="side-card-title">{{ __('Popular posts', 'sage') }}</h2>
      <ul class="side-posts">
        @foreach ($popular as $p)
          <li>
            <a href="{{ esc_url($p['url']) }}">{{ $p['title'] }}</a>
            <span class="side-date">{{ $p['date'] }}</span>
          </li>
        @endforeach
      </ul>
    </section>
  @endif

  @if ($cats && ! is_wp_error($cats))
    <section class="side-card" aria-labelledby="side-cat-h">
      <h2 id="side-cat-h" class="side-card-title">{{ __('Topics', 'sage') }}</h2>
      <ul class="side-cats">
        @foreach ($cats as $cat)
          <li>
            <a href="{{ esc_url(get_category_link($cat)) }}">{{ $cat->name }}</a>
            <span class="side-count">{{ (int) $cat->count }}</span>
          </li>
        @endforeach
      </ul>
    </section>
  @endif

  <section class="side-card" aria-labelledby="side-sub-h">
    <h2 id="side-sub-h" class="side-card-title">{{ __('Subscribe', 'sage') }}</h2>
    <p>{{ __('New notes in your reader. No ads.', 'sage') }}</p>
    <a class="btn" href="{{ esc_url(home_url('/feed/')) }}">{!! \App\mh_svg_icon('rss', 18) !!} {{ __('RSS feed', 'sage') }}</a>
  </section>
</aside>
