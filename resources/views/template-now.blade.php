{{--
  Template Name: Now
--}}

@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  <div class="container">
    <div class="entry-content post-prose">@php(the_content())</div>
    <p class="archive-desc now-updated">{{ sprintf(__('Last updated %s.', 'matthummel'), get_the_modified_date()) }}</p>
  </div>
@endsection
