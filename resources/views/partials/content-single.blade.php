<article @php(post_class('post-single h-entry'))>
  <header class="post-single-header">
    <div class="container">
      <p class="post-meta">
        <time class="dt-published" datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time>
        <span aria-hidden="true"> &middot; </span>{{ get_the_author() }}
      </p>
      <h1 class="post-single-title p-name">{!! $title !!}</h1>
    </div>
  </header>

  <div class="entry-content e-content post-prose">
    @php(the_content())
  </div>

  @if ($pagination())
    <footer>
      <nav class="page-nav" aria-label="{{ __('Page', 'sage') }}">
        {!! $pagination !!}
      </nav>
    </footer>
  @endif

  @php(comments_template())
</article>
