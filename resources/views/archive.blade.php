@extends('layouts.app')

@section('content')
@php
  $writeId = \App\mh_writing_id();
  $writeUrl = $writeId ? get_permalink($writeId) : home_url('/blog/');
  $journal = \App\field('write_h1', __('Journal', 'sage'), $writeId);
@endphp
  @component('partials.page-hero')
    <p class="eyebrow">{{ \App\field('write_kicker', __('Journal', 'sage'), $writeId) }}</p>
    <h1 class="display-title is-hero">{!! get_the_archive_title() !!}</h1>
    @if (get_the_archive_description())
      <div class="lead">{!! get_the_archive_description() !!}</div>
    @endif
    <nav class="write-crumbs" aria-label="{{ __('Breadcrumb', 'sage') }}">
      <a href="{{ esc_url($writeUrl) }}">{{ $journal }}</a>
      <span aria-hidden="true"> / </span>
      <span>
        @if (is_category())
          {{ single_cat_title('', false) }}
        @elseif (is_tag())
          {{ single_tag_title('', false) }}
        @elseif (is_year())
          {{ get_query_var('year') }}
        @elseif (is_month())
          {{ get_the_date('F Y') }}
        @else
          {{ trim(wp_strip_all_tags(get_the_archive_title())) }}
        @endif
      </span>
    </nav>
  @endcomponent

  @include('partials.page-nav', [
    'pills' => [
      ['journal-topics', __('Topics', 'sage')],
      ['journal-posts', __('Posts', 'sage')],
    ],
  ])

  <div class="container wide page-block write-hub" data-journal data-journal-home="{{ esc_url($writeUrl) }}" data-journal-list="post-list">
    @include('partials.write-toolbar', compact('writeId', 'writeUrl'))
    <div id="journal-topics" data-journal-region="topics">
      @include('partials.write-topics', compact('writeId', 'writeUrl'))
    </div>
    <div class="write-layout">
      <div class="write-main" id="journal-posts" data-journal-region="results">
        @include('partials.journal-results', ['writeUrl' => $writeUrl, 'listClass' => 'post-list', 'emptyText' => __('No posts in this topic yet.', 'sage')])
      </div>
      @include('partials.write-aside', compact('writeId'))
    </div>
    @include('partials.write-subscribe', compact('writeId'))
  </div>
@endsection
