{{--
  Template Name: Resources
  Simplified to the_content() so blocks drive the layout.
  Use the "Resources – Link groups" pattern from Patterns → Matthummel to get started.
--}}
@extends('layouts.app')

@section('content')
  @php $postClasses = implode(' ', get_post_class('page-resources')); @endphp
  <article class="{{ $postClasses }}" aria-label="{{ get_the_title() }}">
    @php the_content(); @endphp
  </article>
@endsection
