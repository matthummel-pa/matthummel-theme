{{-- Reading progress bar --}}
<div class="mh-progress" id="mh-progress" role="progressbar" aria-label="Reading progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>

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
  $postDate  = get_the_date('M j, Y');
  $postIso   = get_post_time('c', true);
  $writing   = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $shareUrls = \App\mh_post_share_urls($postId);
  $shareBsky = $shareUrls['bluesky'];
  $shareIn   = $shareUrls['linkedin'];
  $shareFb   = $shareUrls['facebook'];
  $shareReddit = $shareUrls['reddit'];
  $hasAffiliateLinks = \App\mh_post_has_affiliate_links($postId);

  // Related posts — same category, exclude current
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
        'ex'    => wp_trim_words(get_the_excerpt($rp), 14),
      ];
    }
    wp_reset_postdata();
  }
@endphp

<article class="post-single h-entry" id="post-{{ $postId }}"
  itemscope itemtype="https://schema.org/BlogPosting">
  <meta itemprop="author" content="Matt Hummel">
  <meta itemprop="publisher" content="matthummel.com">
  <link itemprop="url" href="{{ esc_url($postUrl) }}">
  <meta itemprop="datePublished" content="{{ esc_attr($postIso) }}">
  @if ($postCats)<meta itemprop="articleSection" content="{{ esc_attr($postCats[0]->name) }}">@endif

  {{-- ── POST HERO ────────────────────────────────────────── --}}
  <header class="post-hero" aria-labelledby="post-title-{{ $postId }}">
    <div class="container wide post-hero-inner">
      <div class="post-hero-main">

      {{-- Breadcrumb / category --}}
      <nav class="post-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ $writing }}">Journal</a>
        @if ($postCats)
          <span aria-hidden="true">›</span>
          <a href="{{ esc_url(get_category_link($postCats[0]->term_id)) }}" itemprop="articleSection">
            {{ $postCats[0]->name }}
          </a>
        @endif
      </nav>

      {{-- Title --}}
      <h1 id="post-title-{{ $postId }}" class="post-hero-title p-name" itemprop="headline">
        {{ get_the_title() }}
      </h1>

      {{-- Meta row --}}
      <div class="post-hero-meta">
        <span class="post-hero-meta__author">
          @include('partials.profile-photo', ['size' => 32, 'class' => 'profile-photo profile-photo--post', 'decorative' => true])
          <span itemprop="author">{{ get_the_author() }}</span>
        </span>
        <span class="post-hero-meta__sep" aria-hidden="true">·</span>
        <time class="dt-published post-hero-meta__date" datetime="{{ $postIso }}">{{ $postDate }}</time>
        <span class="post-hero-meta__sep" aria-hidden="true">·</span>
        <span class="post-hero-meta__read">
          {!! \App\mh_svg_icon('book-open', 14) !!}
          {{ $readMins }} min read
        </span>
        @if (get_comments_number() > 0)
          <span class="post-hero-meta__sep" aria-hidden="true">·</span>
          <a class="post-hero-meta__comments" href="#comments">
            {{ number_format_i18n((int) get_comments_number()) }} {{ _n('comment', 'comments', get_comments_number(), 'sage') }}
          </a>
        @endif
      </div>

      {{-- Hero share --}}
      <div class="post-hero-share">
        <span class="post-hero-share__label">Share</span>
        <a class="post-share-btn" href="{{ esc_url($shareBsky) }}" rel="noopener" target="_blank" aria-label="Share on Bluesky">
          {!! \App\mh_svg_icon('bluesky', 15) !!} Bluesky
        </a>
        <a class="post-share-btn" href="{{ esc_url($shareIn) }}" rel="noopener" target="_blank" aria-label="Share on LinkedIn">
          {!! \App\mh_svg_icon('linkedin', 15) !!} LinkedIn
        </a>
        <a class="post-share-btn" href="{{ esc_url($shareFb) }}" rel="noopener" target="_blank" aria-label="Share on Facebook">
          {!! \App\mh_svg_icon('facebook', 15) !!} Facebook
        </a>
        <a class="post-share-btn" href="{{ esc_url($shareReddit) }}" rel="noopener" target="_blank" aria-label="Share on Reddit">
          {!! \App\mh_svg_icon('reddit', 15) !!} Reddit
        </a>
        <button class="post-share-btn post-copy-link" type="button" data-copy="{{ esc_attr($postUrl) }}" aria-label="Copy link">
          {!! \App\mh_svg_icon('share', 15) !!} <span>Copy link</span>
        </button>
      </div>

      </div>
    </div>
  </header>

  {{-- ── CONTENT LAYOUT ──────────────────────────────────── --}}
  <div class="container wide post-shell">
    <div class="post-layout">

      {{-- Main column --}}
      <div class="post-main">

        {{-- Featured image --}}
        @if (has_post_thumbnail())
          <figure class="post-featured" itemprop="image">
            {!! get_the_post_thumbnail($postId, 'large', ['class' => 'post-featured-img-el', 'loading' => 'eager']) !!}
          </figure>
        @endif

        {{-- Mobile TOC --}}
        @if ($toc)
          <details class="mh-toc mh-toc--inline" open>
            <summary class="mh-toc-title">On this page</summary>
            <ol>
              @foreach ($toc as $item)
                <li class="side-toc-h{{ $item['level'] }}">
                  <a href="#{{ esc_attr($item['id']) }}">{{ $item['text'] }}</a>
                </li>
              @endforeach
            </ol>
          </details>
        @endif

        {{-- Article body --}}
        @if ($hasAffiliateLinks)
          <aside class="post-extra-note affiliate-disclosure" aria-label="Affiliate disclosure">
            <strong>Affiliate disclosure:</strong>
            I may earn a commission if you buy through links in this post, at no extra cost to you. I only recommend tools I would use on a real project. The same disclosure covers Uses and Resources.
            <a href="{{ home_url('/affiliate-disclosure/') }}">How affiliate links work on this site.</a>
          </aside>
        @endif
        <div class="entry-content e-content post-prose" id="post-prose" itemprop="articleBody">
          {!! $bodyHtml !!}
        </div>

        {{-- Tags --}}
        @if ($postTags)
          <div class="post-tags" aria-label="Tags">
            @foreach ($postTags as $tag)
              <a class="post-tag" href="{{ esc_url(get_tag_link($tag->term_id)) }}">#{{ $tag->name }}</a>
            @endforeach
          </div>
        @endif

        {{-- Bottom share --}}
        <div class="post-share-bottom">
          <p class="post-share-bottom__prompt">Found this useful?</p>
          <div class="post-share-bottom__btns">
            <a class="post-share-btn" href="{{ esc_url($shareBsky) }}" rel="noopener" target="_blank" aria-label="Share on Bluesky">
              {!! \App\mh_svg_icon('bluesky', 15) !!} Bluesky
            </a>
            <a class="post-share-btn" href="{{ esc_url($shareIn) }}" rel="noopener" target="_blank" aria-label="Share on LinkedIn">
              {!! \App\mh_svg_icon('linkedin', 15) !!} LinkedIn
            </a>
            <a class="post-share-btn" href="{{ esc_url($shareFb) }}" rel="noopener" target="_blank" aria-label="Share on Facebook">
              {!! \App\mh_svg_icon('facebook', 15) !!} Facebook
            </a>
            <a class="post-share-btn" href="{{ esc_url($shareReddit) }}" rel="noopener" target="_blank" aria-label="Share on Reddit">
              {!! \App\mh_svg_icon('reddit', 15) !!} Reddit
            </a>
            <button class="post-share-btn post-copy-link" type="button" data-copy="{{ esc_attr($postUrl) }}" aria-label="Copy link">
              {!! \App\mh_svg_icon('share', 15) !!} <span>Copy link</span>
            </button>
          </div>
        </div>

        {{-- Snippet / extra code note --}}
        <p class="post-extra-note">
          {!! \App\field_html('write_share_note', __('More examples on the <a href="/code/">Code</a> page. Questions about a snippet? <a href="/contact/">Say hello</a>.', 'sage'), \App\mh_writing_id()) !!}
        </p>

        {{-- Author bio --}}
        <div class="post-author-bio">
          @include('partials.profile-photo', ['size' => 80, 'class' => 'profile-photo post-author-bio__photo', 'decorative' => false])
          <div class="post-author-bio__body">
            <p class="post-author-bio__name">{{ get_the_author() }}</p>
            <p class="post-author-bio__role">Full-stack developer · WordPress specialist</p>
            <p class="post-author-bio__desc">
              {{ get_the_author_meta('description') ?: 'I write about WordPress, PHP, and the tools I use on real projects — usually with code you can paste in.' }}
            </p>
            <div class="post-author-bio__links">
              <a href="{{ home_url('/about/') }}">About me</a>
              <a href="{{ home_url('/services/') }}">Work with me</a>
              <a href="{{ home_url('/contact/') }}">Say hello</a>
              <a href="{{ home_url('/feed/') }}" rel="alternate" type="application/rss+xml">RSS feed</a>
            </div>
          </div>
        </div>

        {{-- Post-end CTA — category-aware (WordPress / full-stack default; Power Platform when relevant) --}}
        @php
          $catBlob  = strtolower(implode(' ', array_map(
            static fn ($c) => trim(($c->name ?? '').' '.($c->slug ?? '')),
            is_array($postCats) ? $postCats : []
          )));
          $titleLow = strtolower(get_the_title());
          $isPower  = str_contains($catBlob, 'power')
            || str_contains($titleLow, 'power apps')
            || str_contains($titleLow, 'power automate')
            || str_contains($titleLow, 'power platform');
          $ctaHead  = $isPower
            ? __('Building something with Power Platform?', 'sage')
            : __('Building WordPress or full-stack work?', 'sage');
          $ctaBody  = $isPower
            ? __('Questions about Power Apps, Power Automate, or connectors are welcome — a quick formula note or a full build. I also take WordPress and full-stack web work, remote anywhere.', 'sage')
            : __('A question about this post is just as welcome as a project note. I build WordPress platforms and full-stack web apps for shops and agencies — remote anywhere.', 'sage');
          $ctaId = 'post-cta-heading';
        @endphp
        <aside class="post-cta" aria-labelledby="{{ $ctaId }}">
          <div class="post-cta__copy">
            <p class="post-cta__eyebrow">{{ __('Work together', 'sage') }}</p>
            <h2 id="{{ $ctaId }}" class="post-cta__heading">{{ $ctaHead }}</h2>
            <p class="post-cta__body">{{ $ctaBody }}</p>
          </div>
          <div class="post-cta__actions">
            <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
              {!! \App\mh_svg_icon('mail', 16) !!}
              {{ __('Say hello', 'sage') }}
            </a>
            <a class="btn btn-ghost" href="{{ home_url('/hire/') }}">
              {{ __('Hire me', 'sage') }}
            </a>
            <p class="post-cta__note">{{ __('Remote · usually within a day', 'sage') }}</p>
          </div>
        </aside>

        {{-- Prev / Next --}}
        @php
          $prevPost = get_previous_post();
          $nextPost = get_next_post();
        @endphp
        @if ($prevPost || $nextPost)
          <nav class="post-nav" aria-label="Post navigation">
            @if ($prevPost)
              <a class="post-nav__link post-nav__link--prev" href="{{ get_permalink($prevPost) }}">
                <span class="post-nav__dir">← Previous post</span>
                <span class="post-nav__title">{{ get_the_title($prevPost) }}</span>
              </a>
            @else
              <span></span>
            @endif
            @if ($nextPost)
              <a class="post-nav__link post-nav__link--next" href="{{ get_permalink($nextPost) }}">
                <span class="post-nav__dir">Next post →</span>
                <span class="post-nav__title">{{ get_the_title($nextPost) }}</span>
              </a>
            @endif
          </nav>
        @endif

        {{-- Related posts --}}
        @if (! empty($relatedPosts))
          <section class="post-related" aria-labelledby="related-heading">
            <h2 id="related-heading" class="post-related__heading">
              More from the journal
              @if ($postCats)<span class="post-related__cat">in {{ $postCats[0]->name }}</span>@endif
            </h2>
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
                    @if ($rp['ex'])<p class="post-related-card__ex">{{ $rp['ex'] }}</p>@endif
                    <p class="post-related-card__meta">{{ $rp['date'] }}@if($rp['mins']) · {{ $rp['mins'] }} min @endif</p>
                  </div>
                </article>
              @endforeach
            </div>
            <a class="h-text-arrow" href="{{ $writing }}" style="display:inline-flex;margin-top:1.5rem">All posts →</a>
          </section>
        @endif

        @php comments_template(); @endphp
      </div>

      {{-- Sidebar --}}
      @include('partials.post-sidebar', ['postId' => $postId, 'summary' => $summary, 'toc' => $toc])

    </div>
  </div>
</article>
