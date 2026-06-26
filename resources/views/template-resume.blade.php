{{--
  Template Name: Résumé
  Simplified to the_content() so blocks drive the layout.
  Use the "Full page – Résumé" pattern from Patterns → Matthummel to get started.
--}}
@extends('layouts.app')

@section('content')
  @php $postClasses = implode(' ', get_post_class('page-resume')); @endphp
  <article class="{{ $postClasses }}" aria-label="{{ get_the_title() }}">
    @php the_content(); @endphp
  </article>
@endsection
