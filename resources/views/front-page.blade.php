{{-- Front page: the Home page's blocks (hero, projects, journal, services, questions, closing band). --}}
@extends('layouts.app')

@section('content')
  @if (is_page())
    @while (have_posts())
      @php(the_post())
      @include('partials.block-page')
    @endwhile
  @else
    {{-- No static front page is set: show the newest posts. --}}
    @include('partials.journal-results')
  @endif
@endsection
