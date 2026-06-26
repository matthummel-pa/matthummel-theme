{{--
  Front page — renders block content from the static front page (Settings → Reading).
  Blocks drive the layout; no hardcoded sections here.
--}}
@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    <article @php(post_class('page-home')) aria-label="{{ get_the_title() }}">
      @php(the_content())
    </article>
  @endwhile
@endsection
