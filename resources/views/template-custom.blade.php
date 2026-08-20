{{--
  Template Name: Custom Template
--}}
@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @include('partials.page-header')
    <div class="container measure" style="padding-bottom:3rem">
      @include('partials.content-page')
    </div>
  @endwhile
@endsection
