{{--
  Template Name: Thank You
--}}
@extends('layouts.app')

@section('content')

<div class="ty-wrap">
  <div class="container ty-inner">

    <div class="ty-icon" aria-hidden="true">{!! \App\mh_svg_icon('check', 28) !!}</div>

    <h1 class="ty-heading">Got it.</h1>
    <p class="ty-lede">Your message is in my inbox. I usually reply within a day — two at most.</p>
    <p class="ty-sub">If your question is about a post or snippet, feel free to leave a comment on it too.</p>

    <nav class="ty-links" aria-label="Keep browsing">
      <a href="{{ home_url('/projects/') }}">
        {!! \App\mh_svg_icon('globe', 15) !!} See example sites
      </a>
      <a href="{{ home_url('/services/') }}">
        {!! \App\mh_svg_icon('wordpress', 15) !!} Services
      </a>
      <a href="{{ get_permalink(get_option('page_for_posts')) ?: home_url('/blog/') }}">
        {!! \App\mh_svg_icon('pen', 15) !!} Journal
      </a>
      <a href="{{ home_url('/about/') }}">
        {!! \App\mh_svg_icon('user', 15) !!} About
      </a>
    </nav>

    <a class="ty-home" href="{{ home_url('/') }}">← Back to home</a>

  </div>
</div>

@endsection
