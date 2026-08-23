@extends('layouts.app')

@section('content')
@php
  $writeId = \App\mh_writing_id();
  $writeUrl = $writeId ? get_permalink($writeId) : home_url('/blog/');
@endphp
  @component('partials.page-hero', ['tag' => 'div'])
    <p class="eyebrow">{{ __('Search', 'sage') }}</p>
    <h1 class="display-title is-hero">{{ __('Search results', 'sage') }}</h1>
    <p class="lead">{{ sprintf(__('Showing results for “%s”', 'sage'), get_search_query()) }}</p>
    <div class="search-wrap write-hero-search">
      @include('forms.search', ['placeholder' => \App\field('write_search_ph', __('Search posts', 'sage'), $writeId)])
    </div>
  @endcomponent

  <div class="container wide page-block write-hub">
    @include('partials.write-toolbar', ['writeId' => $writeId, 'writeUrl' => $writeUrl, 'hideSearch' => true])
    @include('partials.write-topics', compact('writeId', 'writeUrl'))
    @if (! have_posts())
      <p class="archive-desc">{{ __('Nothing matched. Try another search, or browse the journal and Code.', 'sage') }}</p>
      <p class="btn-row">
        <a class="btn" href="{{ esc_url($writeUrl) }}">{{ \App\field('write_h1', __('Journal', 'sage'), $writeId) }}</a>
        <a class="btn btn-outline" href="{{ home_url('/code/') }}">{{ __('Code', 'sage') }}</a>
      </p>
    @else
      <div class="write-layout">
        <div class="write-main" id="journal-posts">
          <div class="post-list" data-post-list>
            @while(have_posts()) @php(the_post())
              @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
            @endwhile
          </div>
          <div class="posts-nav">
            {!! get_the_posts_pagination([
              'mid_size' => 1,
              'prev_text' => __('Previous', 'sage'),
              'next_text' => __('Next', 'sage'),
            ]) !!}
          </div>
        </div>
        @include('partials.write-aside', compact('writeId'))
      </div>
    @endif
  </div>
@endsection
