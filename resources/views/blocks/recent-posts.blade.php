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
        <a class="shop-link shop-link--forward" href="{{ esc_url($writing) }}">{{ $linkText }} <span class="shop-link__icon" aria-hidden="true">&rarr;</span></a>
      @endif
      @if ($showRss)
        <a class="shop-link shop-link--forward" href="{{ esc_url(home_url('/feed/')) }}" rel="alternate" type="application/rss+xml">
          {!! \App\mh_svg_icon('rss', 14) !!} RSS <span class="shop-link__icon" aria-hidden="true">&rarr;</span>
        </a>
      @endif
    </div>
  @endif
  @if ($posts !== [])
    <div class="h-journal-list">
      @foreach ($posts as $i => $post)
        <article class="post-card" itemscope itemtype="https://schema.org/BlogPosting">
          <meta itemprop="author" content="Matt Hummel">
          @if (! empty($post['thumb']))
            <a class="post-shot" href="{{ esc_url($post['url']) }}" tabindex="-1" aria-hidden="true">
              <img src="{{ esc_url($post['thumb']) }}" alt="" width="960" height="540" loading="lazy" decoding="async" itemprop="image">
            </a>
          @endif
          <div class="post-body">
            @if ($post['cat'])
              <div class="post-cats project-type-row">
                <a class="post-cat project-cat-pill" href="{{ esc_url($post['cat_url'] ?? $writing) }}" itemprop="articleSection">{{ $post['cat'] }}</a>
              </div>
            @endif
            <p class="post-meta">
              <time datetime="{{ esc_attr($post['date_iso'] ?? '') }}" itemprop="datePublished">{{ $post['date'] }}</time>
              @if (! empty($post['minutes']))
                <span aria-hidden="true"> · </span>
                {{-- translators: %s: reading time in minutes. --}}
                {{ sprintf(__('%s min read', 'sage'), $post['minutes']) }}
              @endif
            </p>
            <h3 class="post-card-title" itemprop="headline">
              <a href="{{ esc_url($post['url']) }}">{{ $post['title'] }}</a>
            </h3>
            @if (! empty($post['ex']))
              <div class="post-card-excerpt"><p itemprop="description">{{ $post['ex'] }}</p></div>
            @endif
            @include('partials.read-more', ['url' => $post['url'], 'name' => $post['title']])
          </div>
        </article>
      @endforeach
    </div>
  @else
    <p class="h-journal__empty">{{ __('No posts yet.', 'sage') }}</p>
  @endif
</div>
