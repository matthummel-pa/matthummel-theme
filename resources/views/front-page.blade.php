{{--
  Front page — renders block content from the static front page (Settings → Reading).
  Blocks drive the layout; no hardcoded sections here.
--}}
@extends('layouts.app')

@section('content')
  @while(have_posts())
    @php the_post(); $postClasses = implode(' ', get_post_class('page-home')); @endphp
    <article class="{{ $postClasses }}" aria-label="{{ get_the_title() }}">
      @php the_content(); @endphp
    </article>
  @endwhile
@endsection
