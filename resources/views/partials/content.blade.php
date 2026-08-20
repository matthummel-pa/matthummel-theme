@php
  $thumb = \App\mh_post_card_image(get_the_ID());
  $minutes = \App\mh_reading_minutes(get_the_ID());
  $cats = get_the_category();
  $hasCode = \App\mh_post_has_code(get_the_ID());
  $featured = ! empty($featured);
@endphp
<article @php(post_class(['post-card', $featured ? 'post-card--featured' : '']))>
  @if ($thumb !== '')
    <span class="post-shot" aria-hidden="true">
      <img src="{{ esc_url($thumb) }}" alt="" width="960" height="540" loading="{{ $featured ? 'eager' : 'lazy' }}" decoding="async">
    </span>
  @else
    <span class="post-shot post-shot--fallback" aria-hidden="true">
      <span class="post-shot-fallback">{{ wp_trim_words(get_the_title(), 4, '') }}</span>
    </span>
  @endif
  <div class="post-body">
    @if ($featured)
      <p class="eyebrow">{{ __('Latest', 'sage') }}</p>
    @endif
    <p class="post-meta">
      <time datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time>
      <span aria-hidden="true"> · </span>
      {{ sprintf(_n('%d min read', '%d min read', $minutes, 'sage'), $minutes) }}
      @if ($hasCode)
        <span class="post-badge">{{ __('Code', 'sage') }}</span>
      @endif
    </p>
    @if ($cats && ! is_wp_error($cats))
      <p class="post-cats">
        @foreach ($cats as $cat)
          <a class="post-cat" href="{{ esc_url(get_category_link($cat)) }}">{{ $cat->name }}</a>
        @endforeach
      </p>
    @endif

    <h2 class="post-card-title">{!! $title !!}</h2>

    <div class="post-card-excerpt">
      @php(the_excerpt())
    </div>

    @include('partials.read-more')
  </div>
</article>
