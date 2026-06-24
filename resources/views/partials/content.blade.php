<article @php(post_class('post-card'))>
  <p class="post-meta">
    <time datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time>
    <span aria-hidden="true"> &middot; </span>{{ get_the_author() }}
  </p>

  <h2 class="post-card-title">
    <a href="{{ get_permalink() }}">{!! $title !!}</a>
  </h2>

  <div class="post-card-excerpt">
    @php(the_excerpt())
  </div>

  <a class="post-card-more" href="{{ get_permalink() }}">
    {{ __('Read more', 'sage') }} <span aria-hidden="true">&rarr;</span>
  </a>
</article>
