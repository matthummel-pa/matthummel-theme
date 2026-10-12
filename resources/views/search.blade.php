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

@include('partials.page-nav', [
  'pills' => [
    ['journal-topics', __('Topics', 'sage')],
    ['journal-posts', __('Results', 'sage')],
  ],
])

  <div class="container wide page-block write-hub" data-journal data-journal-home="{{ esc_url($writeUrl) }}" data-journal-list="post-list">
    @include('partials.write-toolbar', ['writeId' => $writeId, 'writeUrl' => $writeUrl, 'hideSearch' => true])
    <div id="journal-topics" data-journal-region="topics">
      @include('partials.write-topics', compact('writeId', 'writeUrl'))
    </div>
    <div class="write-layout">
      <div class="write-main" id="journal-posts" data-journal-region="results">
        @include('partials.journal-results', ['writeUrl' => $writeUrl, 'listClass' => 'post-list', 'emptyText' => __('Nothing matched. Try another search, or pick a topic.', 'sage')])
      </div>
      @include('partials.write-aside', compact('writeId'))
    </div>
  </div>
@endsection
