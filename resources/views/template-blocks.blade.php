{{--
  Template Name: Block page
  A page built from blocks: Page hero, Section, Card grid, Questions, and the core blocks inside them.
--}}
@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php(the_post())
    @include('partials.block-page')
  @endwhile
@endsection
