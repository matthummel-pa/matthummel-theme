{{-- matthummel/recent-posts: the newest Journal posts (same list as the old Home section). --}}
@php
  $count = min(9, max(1, (int) ($attributes['count'] ?? 3)));
  $posts = \App\mh_home_journal_posts($count);
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $linkText = trim((string) ($attributes['linkText'] ?? ''));
  $showRss = ! empty($attributes['showRss']);
@endphp
<div class="{{ trim('mh-data mh-recent-posts '.($attributes['className'] ?? '')) }}">
  @if ($linkText !== '' || $showRss)
    <div class="mh-data__meta">
      @if ($linkText !== '')
        <a class="h-text-arrow" href="{{ esc_url($writing) }}">{{ $linkText }}</a>
      @endif
      @if ($showRss)
        <a class="h-journal__rss" href="{{ esc_url(home_url('/feed/')) }}" rel="alternate" type="application/rss+xml">
          {!! \App\mh_svg_icon('rss', 14) !!} RSS
        </a>
      @endif
    </div>
  @endif
  @if ($posts !== [])
    <div class="h-journal-list">
      @foreach ($posts as $i => $post)
        <article class="h-journal-item" itemscope itemtype="https://schema.org/BlogPosting">
          <meta itemprop="author" content="Matt Hummel">
          @if (! empty($post['thumb']))
            <a class="h-journal-item__thumb" href="{{ esc_url($post['url']) }}" tabindex="-1" aria-hidden="true">
              <img src="{{ esc_url($post['thumb']) }}" alt="" width="320" height="200" loading="lazy" decoding="async" itemprop="image">
            </a>
          @endif
          <div class="h-journal-item__body">
            <div class="h-journal-item__meta">
              @if ($post['cat'])
                <a class="h-journal__cat project-cat-pill" href="{{ esc_url($post['cat_url'] ?? $writing) }}" itemprop="articleSection">{{ $post['cat'] }}</a>
              @endif
              <time datetime="{{ esc_attr($post['date_iso'] ?? '') }}" itemprop="datePublished">{{ $post['date'] }}</time>
              @if (! empty($post['minutes']))
                {{-- translators: %s: reading time in minutes. --}}
                <span>{{ sprintf(__('%s min', 'sage'), $post['minutes']) }}</span>
              @endif
            </div>
            <h3 class="h-journal-item__title" itemprop="headline">
              <a href="{{ esc_url($post['url']) }}">{{ $post['title'] }}</a>
            </h3>
            @if (! empty($post['ex']))
              <p class="h-journal-item__ex" itemprop="description">{{ $post['ex'] }}</p>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @else
    <p class="h-journal__empty">{{ __('No posts yet.', 'sage') }}</p>
  @endif
</div>
