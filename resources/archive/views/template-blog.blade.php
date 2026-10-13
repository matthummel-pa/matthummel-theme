{{--
  Template Name: Blog
  Posts page uses index.blade.php. This template is unused.
--}}
@extends('layouts.app')

@section('content')
  <div class="container" style="padding:2rem 0">
    <p><a href="{{ get_permalink(get_option('page_for_posts')) ?: home_url('/blog/') }}">See journal</a></p>
  </div>
@endsection
