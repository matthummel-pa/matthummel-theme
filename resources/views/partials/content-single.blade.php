{{-- Reading progress bar --}}
<div class="mh-progress" id="mh-progress" role="progressbar" aria-label="{{ __('Reading progress', 'sage') }}" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>

@php
  $postId    = get_the_ID();
  $postObj   = get_post($postId);
  $postCats  = get_the_category();
  $postTags  = get_the_tags();
  $raw       = apply_filters('the_content', $postObj ? $postObj->post_content : get_the_content());
  [$bodyHtml, $toc] = \App\mh_content_with_toc($raw);
  $summary   = $postObj instanceof \WP_Post ? \App\mh_post_summary($postObj) : '';
  $words     = str_word_count(wp_strip_all_tags($bodyHtml));
  $readMins  = max(1, (int) round($words / 200));
  $postUrl   = get_permalink($postId);
  $postTitle = get_the_title();
  $shareX    = 'https://twitter.com/intent/tweet?text='.rawurlencode($postTitle).'&url='.rawurlencode($postUrl);
  $shareIn   = 'https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($postUrl);
  $writing   = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');

  // Related posts: same category, exclude current
  $relatedPosts = [];
  if ($postCats) {
    $relQ = new WP_Query([
      'post_type'      => 'post',
      'posts_per_page' => 3,
      'post__not_in'   => [$postId],
      'category__in'   => array_column($postCats, 'term_id'),
      'orderby'        => 'date',
      'order'          => 'DESC',
      'no_found_rows'  => true,
    ]);
    foreach ($relQ->posts as $rp) {
      $rc = get_the_category($rp->ID);
      $relatedPosts[] = [
        'title' => get_the_title($rp),
        'url'   => get_permalink($rp),
        'date'  => get_the_date('M j, Y', $rp),
        'cat'   => $rc ? $rc[0]->name : '',
        'thumb' => \App\mh_post_card_image((int) $rp->ID),
        'mins'  => \App\mh_reading_minutes($rp),
      ];
    }
    wp_reset_postdata();
  }
@endphp

<article class="post-single h-entry" id="post-{{ $postId }}" itemscope itemtype="https://schema.org/BlogPosting">
  <meta itemprop="author" content="Matt Hummel">
  <meta itemprop="publisher" content="matthummel.com">
  @if (!empty($postUrl))<link itemprop="url" href="{{ esc_url($postUrl) }}">@endif

  {{-- Post hero --}}
  <header class="post-hero">
    <div class="container wide post-hero-inner">
      @if ($postCats)
        <a class="post-hero-tag" href="{{ esc_url(get_category_link($postCats[0]->term_id)) }}" itemprop="articleSection">
          {{ $postCats[0]->name }}
        </a>
      @endif

      <h1 class="post-hero-title p-name" itemprop="headline">{!! get_the_title() !!}</h1>

      <div class="post-hero-meta">
        @include('partials.profile-photo', ['size' => 36, 'class' => 'profile-photo profile-photo--post', 'decorative' => true])
        <span class="post-hero-author" itemprop="author">{{ get_the_author() }}</span>
        <span class="post-hero-sep" aria-hidden="true">·</span>
        <time class="dt-published" datetime="{{ get_post_time('c', true) }}" itemprop="datePublished">{{ get_the_date('M j, Y') }}</time>
        <span class="post-hero-sep" aria-hidden="true">·</span>
        <span class="post-hero-read">{!! \App\mh_svg_icon('book-open', 14) !!} {{ $readMins }} min read</span>
        @if (comments_open() || get_comments_number())
          <span class="post-hero-sep" aria-hidden="true">·</span>
          <a class="post-hero-comments" href="#comments">
            {{ sprintf(_n('%s comment', '%s comments', get_comments_number(), 'sage'), number_format_i18n((int) get_comments_number())) }}
          </a>
        @endif
      </div>

      {{-- Share bar in hero --}}
      <div class="post-share-bar post-share-bar--hero">
        <span class="post-share-bar__label">Share</span>
        <a class="post-share-btn" href="{{ esc_url($shareX) }}" rel="noopener" target="_blank" aria-label="Share on X (Twitter)">
          {!! \App\mh_svg_icon('twitter', 16) !!}
          <span>X</span>
        </a>
        <a class="post-share-btn" href="{{ esc_url($shareIn) }}" rel="noopener" target="_blank" aria-label="Share on LinkedIn">
          {!! \App\mh_svg_icon('linkedin', 16) !!}
          <span>LinkedIn</span>
        </a>
        <button class="post-share-btn post-copy-link" type="button" data-copy="{{ esc_attr($postUrl) }}" aria-label="Copy link">
          {!! \App\mh_svg_icon('rss', 16) !!}
          <span>Copy link</span>
        </button>
      </div>
    </div>
  </header>

  {{-- Main content --}}
  <div class="container wide post-shell">
    <div class="post-layout">
      <div class="post-main">

        @if (has_post_thumbnail())
          <figure class="post-featured" itemprop="image">
            {!! get_the_post_thumbnail($postId, 'large', ['class' => 'post-featured-img-el']) !!}
          </figure>
        @endif

        {{-- Inline TOC (mobile) --}}
        @if ($toc)
          <details class="mh-toc mh-toc--inline">
            <summary class="mh-toc-title">On this page</summary>
            <ol>
              @foreach ($toc as $item)
                <li class="side-toc-h{{ $item['level'] }}"><a href="#{{ esc_attr($item['id']) }}">{{ $item['text'] }}</a></li>
              @endforeach
            </ol>
          </details>
        @endif

        {{-- Article body --}}
        <div class="entry-content e-content post-prose" id="post-prose" itemprop="articleBody">
          {!! $bodyHtml !!}
        </div>

        {{-- Tags --}}
        @if ($postTags)
          <div class="post-tags">
            @foreach ($postTags as $tag)
              <a class="post-tag" href="{{ esc_url(get_tag_link($tag->term_id)) }}">#{{ $tag->name }}</a>
            @endforeach
          </div>
        @endif

        {{-- Bottom share --}}
        <div class="post-share-bar post-share-bar--bottom">
          <p class="post-share-bar__prompt">Found this useful? Share it.</p>
          <div class="post-share-bar__btns">
            <a class="post-share-btn" href="{{ esc_url($shareX) }}" rel="noopener" target="_blank">
              {!! \App\mh_svg_icon('twitter', 16) !!} Share on X
            </a>
            <a class="post-share-btn" href="{{ esc_url($shareIn) }}" rel="noopener" target="_blank">
              {!! \App\mh_svg_icon('linkedin', 16) !!} Share on LinkedIn
            </a>
            <button class="post-share-btn post-copy-link" type="button" data-copy="{{ esc_attr($postUrl) }}">
              {!! \App\mh_svg_icon('rss', 16) !!} Copy link
            </button>
          </div>
        </div>

        {{-- Share note --}}
        <p class="post-share-note">
          {!! \App\field_html('write_share_note', __('More examples on the <a href="/code/">Code</a> page. Questions about a snippet? <a href="/contact/">Say hello</a>.', 'sage'), \App\mh_writing_id()) !!}
        </p>

        {{-- Author bio --}}
        <div class="post-author-bio">
          @include('partials.profile-photo', ['size' => 80, 'class' => 'profile-photo profile-photo--bio post-author-bio-avatar'])
          <div class="post-author-bio-body">
            <p class="post-author-bio-name">{{ get_the_author() }}</p>
            <p class="post-author-bio-role">WordPress developer · Gettysburg, PA</p>
            <p class="post-author-bio-desc">
              {{ get_the_author_meta('description') ?: \App\field('write_bio', __('I write from Gettysburg, Pennsylvania. Posts cover WordPress, PHP, and the tools I use on real projects — often with code you can paste in.', 'sage'), \App\mh_writing_id()) }}
            </p>
            <div class="post-author-bio-links">
              <a href="{{ home_url('/about/') }}">About me</a>
              <a href="{{ home_url('/services/') }}">Work with me</a>
              <a href="{{ home_url('/contact/') }}">Say hello</a>
            </div>
          </div>
        </div>

        {{-- Prev / Next --}}
        @php
          $prevPost = get_previous_post();
          $nextPost = get_next_post();
        @endphp
        @if ($prevPost || $nextPost)
          <nav class="post-prev-next" aria-label="Post navigation">
            @if ($prevPost)
              <a class="post-prev-next-link" href="{{ get_permalink($prevPost) }}">
                <span class="post-prev-next-dir">← Previous</span>
                <span class="post-prev-next-title">{{ get_the_title($prevPost) }}</span>
              </a>
            @else
              <span></span>
            @endif
            @if ($nextPost)
              <a class="post-prev-next-link post-prev-next-link--next" href="{{ get_permalink($nextPost) }}">
                <span class="post-prev-next-dir">Next →</span>
                <span class="post-prev-next-title">{{ get_the_title($nextPost) }}</span>
              </a>
            @endif
          </nav>
        @endif

        {{-- Related posts --}}
        @if (! empty($relatedPosts))
          <section class="post-related" aria-labelledby="related-heading">
            <h2 id="related-heading" class="post-related__heading">More from the journal</h2>
            <div class="post-related__grid">
              @foreach ($relatedPosts as $rp)
                <article class="post-related-card">
                  @if (! empty($rp['thumb']))
                    <a class="post-related-card__img" href="{{ esc_url($rp['url']) }}" tabindex="-1" aria-hidden="true">
                      <img src="{{ esc_url($rp['thumb']) }}" alt="{{ esc_attr($rp['title']) }}" width="400" height="225" loading="lazy" decoding="async">
                    </a>
                  @endif
                  <div class="post-related-card__body">
                    @if ($rp['cat'])<span class="post-related-card__cat">{{ $rp['cat'] }}</span>@endif
                    <h3 class="post-related-card__title">
                      <a href="{{ esc_url($rp['url']) }}">{{ $rp['title'] }}</a>
                    </h3>
                    <p class="post-related-card__meta">{{ $rp['date'] }}@if($rp['mins']) · {{ $rp['mins'] }} min @endif</p>
                  </div>
                </article>
              @endforeach
            </div>
            <a class="about-text-link" href="{{ $writing }}" style="display:inline-block;margin-top:1.25rem">All posts →</a>
          </section>
        @endif

        @php comments_template(); @endphp
      </div>

      @include('partials.post-sidebar', ['postId' => $postId, 'summary' => $summary, 'toc' => $toc])
    </div>
  </div>
</article>
