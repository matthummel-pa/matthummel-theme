@if (! post_password_required())
  <section id="comments" class="comments">
    <header class="comments-head">
      <h2 class="comments-title" id="comments-title">
        @if ($responses())
          {!! $title !!}
        @else
          {{ __('Comments', 'sage') }}
        @endif
      </h2>
      <div class="comments-tools">
        @if (comments_open())
          <a class="comments-jump" href="#respond">{{ __('Write a comment', 'sage') }}</a>
        @endif
        @if ($responses())
          <div class="comment-sort" role="group" aria-label="{{ __('Sort comments', 'sage') }}">
            <button type="button" class="comment-sort-btn is-active" data-comment-sort="oldest">{{ __('Oldest', 'sage') }}</button>
            <button type="button" class="comment-sort-btn" data-comment-sort="newest">{{ __('Newest', 'sage') }}</button>
          </div>
        @endif
      </div>
    </header>

    @if ($responses())
      <ol class="comment-list">
        {!! $responses !!}
      </ol>

      @if ($paginated())
        <nav class="comment-pager" aria-label="{{ __('Comment pages', 'sage') }}">
          <ul class="pager">
            @if ($previous())
              <li class="previous">{!! $previous !!}</li>
            @endif
            @if ($next())
              <li class="next">{!! $next !!}</li>
            @endif
          </ul>
        </nav>
      @endif
    @elseif (comments_open())
      <p class="comments-empty">{{ __('No comments yet. ASCII, code, and plain punctuation are welcome.', 'sage') }}</p>
    @endif

    @if ($closed())
      <p class="comments-closed" role="status">{{ __('Comments are closed.', 'sage') }}</p>
    @endif

    @php(comment_form())
  </section>
@endif
