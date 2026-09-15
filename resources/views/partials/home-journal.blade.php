{{-- Home journal digest. Expects $posts and $writing from the parent Home view. --}}
@php
  $journalFeatured = $posts[0] ?? null;
  $journalStack    = array_slice($posts, 1, 4);
  $rssUrl = home_url('/feed/');
@endphp
<section class="h-journal h-band h-band--tint" id="journal" aria-labelledby="h-writing-heading">
  <div class="container wide">

    <div class="h-journal__head">
      <div class="h-journal__head-copy">
        <p class="h-section-label">{{ __('Journal', 'sage') }}</p>
        <h2 id="h-writing-heading" class="h-section__title">
          {{ \App\field('home_write_h2', __('Notes from real WordPress work.', 'sage')) }}
        </h2>
        <p class="h-journal__intro">
          {{ \App\field('home_write_intro', __('Practical posts for shops and developers — handoffs, themes, and lessons from builds. Most include something you can reuse.', 'sage')) }}
        </p>
      </div>
      <div class="h-journal__head-links">
        <a class="h-text-arrow" href="{{ $writing }}">{{ __('All posts', 'sage') }} →</a>
        <a class="h-journal__rss" href="{{ esc_url($rssUrl) }}" rel="alternate" type="application/rss+xml">
          {!! \App\mh_svg_icon('rss', 14) !!} {{ __('RSS feed', 'sage') }}
        </a>
      </div>
    </div>

    @if (! empty($posts))

      <div class="h-journal__grid">

        @if ($journalFeatured)
        @php $fp = $journalFeatured; @endphp
        <article class="h-journal__featured" itemscope itemtype="https://schema.org/BlogPosting">
          <meta itemprop="author" content="Matt Hummel">

          <div class="h-journal__badge">
            {!! \App\mh_svg_icon('pen', 13) !!} {{ __('Featured note', 'sage') }}
          </div>

          @if (! empty($fp['thumb']))
            <a class="h-journal__featured-img-link" href="{{ esc_url($fp['url']) }}" tabindex="-1" aria-hidden="true">
              <div class="h-journal__featured-img">
                <img
                  src="{{ esc_url($fp['thumb']) }}"
                  alt="{{ esc_attr($fp['title']) }}{{ $fp['cat'] ? ' — ' . esc_attr($fp['cat']) . ' post' : '' }}"
                  width="960" height="540"
                  loading="lazy"
                  decoding="async"
                  itemprop="image"
                >
              </div>
            </a>
          @else
            <a class="h-journal__featured-img-link" href="{{ esc_url($fp['url']) }}" tabindex="-1" aria-hidden="true">
              <div class="h-journal__featured-img h-journal__featured-img--text">
                <span>{{ wp_trim_words($fp['title'], 6, '') }}</span>
              </div>
            </a>
          @endif

          <div class="h-journal__featured-body">
            <div class="h-journal__featured-meta">
              @if ($fp['cat'])
                <a class="h-journal__cat" href="{{ esc_url($fp['cat_url'] ?? $writing) }}" itemprop="articleSection">
                  {{ $fp['cat'] }}
                </a>
              @endif
              <time class="h-journal__date" datetime="{{ esc_attr($fp['date_iso'] ?? '') }}" itemprop="datePublished">
                {{ $fp['date'] }}
              </time>
              @if (! empty($fp['minutes']))
                <span class="h-journal__min">
                  {!! \App\mh_svg_icon('book-open', 13) !!}
                  {{ $fp['minutes'] }} min read
                </span>
              @endif
            </div>

            <h3 class="h-journal__featured-title" itemprop="headline">
              <a href="{{ esc_url($fp['url']) }}">{{ $fp['title'] }}</a>
            </h3>

            <p class="h-journal__featured-ex" itemprop="description">{{ $fp['ex'] }}</p>

            <a class="h-journal__read-link" href="{{ esc_url($fp['url']) }}">
              {{ sprintf(__('Read “%s”', 'sage'), $fp['title']) }} <span aria-hidden="true">→</span>
            </a>
          </div>
        </article>
        @endif

        @if (! empty($journalStack))
        <div class="h-journal__stack">
          <p class="h-journal__stack-label">{{ __('More recent posts', 'sage') }}</p>

          @foreach ($journalStack as $post)
            <article class="h-journal__post{{ ! empty($post['deemphasize']) ? ' h-journal__post--quiet' : '' }}" itemscope itemtype="https://schema.org/BlogPosting">
              <meta itemprop="author" content="Matt Hummel">

              @if (! empty($post['thumb']))
                <a class="h-journal__post-thumb" href="{{ esc_url($post['url']) }}" tabindex="-1" aria-hidden="true">
                  <img
                    src="{{ esc_url($post['thumb']) }}"
                    alt="{{ esc_attr($post['title']) }}"
                    width="120" height="80"
                    loading="lazy"
                    decoding="async"
                  >
                </a>
              @else
                <div class="h-journal__post-thumb h-journal__post-thumb--text" aria-hidden="true">
                  {!! \App\mh_svg_icon('pen', 18) !!}
                </div>
              @endif

              <div class="h-journal__post-body">
                @if (! empty($post['deemphasize']))
                  <p class="h-journal__quiet-label">{{ __('Same-day notes', 'sage') }}</p>
                @endif
                @if ($post['cat'])
                  <a class="h-journal__cat h-journal__cat--sm" href="{{ esc_url($post['cat_url'] ?? $writing) }}" itemprop="articleSection">
                    {{ $post['cat'] }}
                  </a>
                @endif
                <h3 class="h-journal__post-title" itemprop="headline">
                  <a href="{{ esc_url($post['url']) }}">{{ $post['title'] }}</a>
                </h3>
                <div class="h-journal__post-meta">
                  <time datetime="{{ esc_attr($post['date_iso'] ?? '') }}" itemprop="datePublished">{{ $post['date'] }}</time>
                  @if (! empty($post['minutes']))
                    <span>· {{ $post['minutes'] }} min</span>
                  @endif
                </div>
              </div>

            </article>
          @endforeach

          <div class="h-journal__stack-footer">
            <a class="h-text-arrow" href="{{ $writing }}">{{ __('Browse all posts', 'sage') }} →</a>
            <a class="h-journal__rss h-journal__rss--sm" href="{{ esc_url($rssUrl) }}" rel="alternate" type="application/rss+xml">
              {!! \App\mh_svg_icon('rss', 13) !!} RSS
            </a>
          </div>
        </div>
        @endif

      </div>

    @else
      <p class="h-journal__empty">{{ \App\field('home_write_empty', __('New posts coming soon.', 'sage')) }}</p>
    @endif

  </div>
</section>
