{{-- Reading progress bar --}}
<div class="mh-progress" id="mh-progress" role="progressbar" aria-label="Reading progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>

@php
  $postId   = get_the_ID();
  $postCats = get_the_category();
  $words    = str_word_count(strip_tags(get_the_content()));
  $readMins = max(1, round($words / 200));
@endphp

<article class="post-single h-entry" id="post-{{ $postId }}">

  {{-- Post hero header --}}
  <header class="post-hero">
    <div class="container post-hero-inner">

      @if ($postCats)
        <a class="post-hero-tag" href="{{ esc_url(get_category_link($postCats[0]->term_id)) }}">
          {{ $postCats[0]->name }}
        </a>
      @endif

      <h1 class="post-hero-title p-name">{!! get_the_title() !!}</h1>

      <div class="post-hero-meta">
        <img
          class="post-hero-avatar"
          src="{{ esc_url(get_avatar_url(get_the_author_meta('ID'), ['size' => 40])) }}"
          alt="{{ get_the_author() }}"
          width="40" height="40"
          loading="lazy"
        >
        <span class="post-hero-author">{{ get_the_author() }}</span>
        <span class="post-hero-sep" aria-hidden="true">&middot;</span>
        <time class="dt-published" datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time>
        <span class="post-hero-sep" aria-hidden="true">&middot;</span>
        <span class="post-hero-read">{{ $readMins }} min read</span>
      </div>

    </div>
  </header>

  {{-- Featured image --}}
  @if (has_post_thumbnail())
    <div class="post-featured-img">
      <div class="container">
        {!! get_the_post_thumbnail($postId, 'large', ['class' => 'post-featured-img-el']) !!}
      </div>
    </div>
  @endif

  {{-- Post layout --}}
  <div class="container">
    <div class="post-layout">
      <div class="post-main">

        {{-- Table of contents (populated by JS from headings) --}}
        <div id="mh-toc-placeholder" class="mh-toc" style="display:none" aria-label="Table of contents">
          <p class="mh-toc-title">Contents</p>
          <ul id="mh-toc-list"></ul>
        </div>

        {{-- Post body --}}
        <div class="entry-content e-content post-prose" id="post-prose">
          @php the_content(); @endphp
        </div>

        {{-- Author bio --}}
        <div class="post-author-bio">
          <img
            class="post-author-bio-avatar"
            src="{{ esc_url(get_avatar_url(get_the_author_meta('ID'), ['size' => 72])) }}"
            alt="{{ get_the_author() }}"
            width="72" height="72"
            loading="lazy"
          >
          <div class="post-author-bio-body">
            <p class="post-author-bio-name">{{ get_the_author() }}</p>
            <p class="post-author-bio-desc">
              {{ get_the_author_meta('description') ?: 'WordPress & Power Platform Developer based in Gettysburg, PA. Building fast, accessible websites and reliable business apps.' }}
            </p>
          </div>
        </div>

        {{-- Prev / Next navigation --}}
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

        @php comments_template(); @endphp

      </div>{{-- /.post-main --}}
    </div>{{-- /.post-layout --}}
  </div>{{-- /.container --}}

</article>
