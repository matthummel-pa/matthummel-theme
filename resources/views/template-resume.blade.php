{{--
  Template Name: Résumé
  Simplified to the_content() so blocks drive the layout.
  Use the "Full page – Résumé" pattern from Patterns → Matthummel to get started.
--}}
@extends('layouts.app')

@section('content')
  <article @php(post_class('page-resume')) aria-label="{{ get_the_title() }}">
    @php(the_content())
  </article>
@endsection
