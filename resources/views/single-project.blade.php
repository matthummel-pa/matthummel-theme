{{--
  WordPress loads single-{post_type}.blade.php for public CPT singles.
  Project pages use the Projects CPT at /projects/{slug}/.
--}}
@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @include('partials.content-single-project')
  @endwhile
@endsection
