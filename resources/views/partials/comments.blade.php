@if (! post_password_required())
  @php
    $isProject = function_exists('\\App\\mh_project_post_type') && get_post_type() === \App\mh_project_post_type();
  @endphp
  <section id="comments" class="comments{{ $isProject ? ' comments--project' : '' }}">
    <header class="comments-head">
      <h2 class="comments-title" id="comments-title">
        @if ($responses())
          {!! $title !!}
        @elseif ($isProject)
          {{ __('Visitor notes', 'sage') }}
        @else
          {{ __('Comments', 'sage') }}
        @endif
      </h2>
      <div class="comments-tools">
        @if (comments_open())
          <a class="comments-jump" href="#respond">{{ $isProject ? __('Leave feedback', 'sage') : __('Write a comment', 'sage') }}</a>
        @endif
        @if ($responses())
          <div class="comment-sort" role="group" aria-label="{{ $isProject ? __('Sort notes', 'sage') : __('Sort comments', 'sage') }}">
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
        <nav class="comment-pager" aria-label="{{ $isProject ? __('Feedback pages', 'sage') : __('Comment pages', 'sage') }}">
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
      <p class="comments-empty">
        @if ($isProject)
          {{ __('No visitor notes yet. Say what you would change, or what you liked. ASCII, code, and plain punctuation are welcome.', 'sage') }}
        @else
          {{ __('No comments yet. ASCII, code, and plain punctuation are welcome.', 'sage') }}
        @endif
      </p>
    @endif

    @if ($closed())
      <p class="comments-closed" role="status">{{ $isProject ? __('Feedback is closed.', 'sage') : __('Comments are closed.', 'sage') }}</p>
    @endif

    @php(comment_form())
  </section>
@endif
