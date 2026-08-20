{{-- Reading progress bar --}}
<div class="mh-progress" id="mh-progress" role="progressbar" aria-label="{{ __('Reading progress', 'sage') }}" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>

@php
  $postId   = get_the_ID();
  $postObj  = get_post($postId);
  $postCats = get_the_category();
  $raw      = apply_filters('the_content', $postObj ? $postObj->post_content : get_the_content());
  [$bodyHtml, $toc] = \App\mh_content_with_toc($raw);
  $summary  = $postObj instanceof \WP_Post ? \App\mh_post_summary($postObj) : '';
  $words    = str_word_count(wp_strip_all_tags($bodyHtml));
  $readMins = max(1, (int) round($words / 200));
@endphp

<article class="post-single h-entry" id="post-{{ $postId }}">

  <header class="post-hero">
    @include('partials.hero-graphic', ['still' => true])
    <div class="container wide post-hero-inner">
      @if ($postCats)
        <a class="post-hero-tag" href="{{ esc_url(get_category_link($postCats[0]->term_id)) }}">
          {{ $postCats[0]->name }}
        </a>
      @endif

      <h1 class="post-hero-title p-name">{!! get_the_title() !!}</h1>

      <div class="post-hero-meta">
        @include('partials.profile-photo', ['size' => 40, 'class' => 'profile-photo profile-photo--post', 'decorative' => true])
        <span class="post-hero-author">{{ get_the_author() }}</span>
        <span class="post-hero-sep" aria-hidden="true">|</span>
        <time class="dt-published" datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time>
        <span class="post-hero-sep" aria-hidden="true">|</span>
        <span class="post-hero-read">{{ $readMins }} min read</span>
        @if (comments_open() || get_comments_number())
          <span class="post-hero-sep" aria-hidden="true">|</span>
          <a class="post-hero-comments" href="#comments">
            {{ sprintf(_n('%s comment', '%s comments', get_comments_number(), 'sage'), number_format_i18n((int) get_comments_number())) }}
          </a>
        @endif
      </div>
    </div>
  </header>

  <div class="container wide post-shell">
    <div class="post-layout">
      <div class="post-main">
        @if (has_post_thumbnail())
          <figure class="post-featured">
            {!! get_the_post_thumbnail($postId, 'large', ['class' => 'post-featured-img-el']) !!}
          </figure>
        @endif

        @if ($toc)
          <details class="mh-toc mh-toc--inline">
            <summary class="mh-toc-title">{{ __('On this page', 'sage') }}</summary>
            <ol>
              @foreach ($toc as $item)
                <li class="side-toc-h{{ $item['level'] }}"><a href="#{{ esc_attr($item['id']) }}">{{ $item['text'] }}</a></li>
              @endforeach
            </ol>
          </details>
        @endif

        <div class="entry-content e-content post-prose" id="post-prose">
          {!! $bodyHtml !!}
        </div>

        <p class="post-share-note">
          {!! \App\field_html('write_share_note', __('Extra copy-paste examples live on the <a href="/code/">Code</a> page. You’re welcome to reuse them. Questions about a snippet? <a href="/contact/">Say hello</a>.', 'sage'), \App\mh_writing_id()) !!}
        </p>

        <div class="post-author-bio">
          @include('partials.profile-photo', ['size' => 72, 'class' => 'profile-photo profile-photo--bio'])
          <div class="post-author-bio-body">
            <p class="post-author-bio-name">{{ get_the_author() }}</p>
            <p class="post-author-bio-desc">
              {{ get_the_author_meta('description') ?: \App\field('write_bio', __('I write notes from Gettysburg, Pennsylvania, and share WordPress, plugin, and other web-app snippets you can paste in. Developers, shops, and agencies are welcome here.', 'sage'), \App\mh_writing_id()) }}
            </p>
          </div>
        </div>

        @php
          $prevPost = get_previous_post();
          $nextPost = get_next_post();
        @endphp
        @if ($prevPost || $nextPost)
          <nav class="post-prev-next" aria-label="{{ __('Post navigation', 'sage') }}">
            @if ($prevPost)
              <a class="post-prev-next-link" href="{{ get_permalink($prevPost) }}">
                <span class="post-prev-next-dir">{{ __('Previous', 'sage') }}</span>
                <span class="post-prev-next-title">{{ get_the_title($prevPost) }}</span>
              </a>
            @else
              <span></span>
            @endif
            @if ($nextPost)
              <a class="post-prev-next-link post-prev-next-link--next" href="{{ get_permalink($nextPost) }}">
                <span class="post-prev-next-dir">{{ __('Next', 'sage') }}</span>
                <span class="post-prev-next-title">{{ get_the_title($nextPost) }}</span>
              </a>
            @endif
          </nav>
        @endif

        @php comments_template(); @endphp
      </div>

      @include('partials.post-sidebar', ['postId' => $postId, 'summary' => $summary, 'toc' => $toc])
    </div>
  </div>
</article>
