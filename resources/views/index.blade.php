@extends('layouts.app')

@section('content')
@php
  $devto = \App\mh_devto_posts(6);
  $writeId = \App\mh_writing_id();
  $writeUrl = $writeId ? get_permalink($writeId) : home_url('/blog/');
  $showFeatured = is_home() && ! is_paged() && have_posts();
@endphp
  @component('partials.page-hero')
    <p class="eyebrow">{{ \App\field('write_kicker', __('Writing', 'sage'), $writeId) }}</p>
    <h1 class="display-title is-hero">{{ \App\field('write_h1', __('Writing, with snippets when they help.', 'sage'), $writeId) }}</h1>
    <p class="lead">{{ \App\field('write_lede', __('Notes on WordPress, plugins, and other web apps. Developers can copy the examples. Shops and agencies can see how I explain a build. I also write on DEV.to.', 'sage'), $writeId) }}</p>
  @endcomponent

  <div class="container wide page-block write-hub">
    @include('partials.write-toolbar', compact('writeId', 'writeUrl'))
    @include('partials.write-topics', compact('writeId', 'writeUrl'))

    @if (! have_posts())
      <p>{{ __('No posts yet.', 'sage') }}</p>
    @else
      @if ($showFeatured)
        @php(the_post())
        @includeFirst(['partials.content-' . get_post_type(), 'partials.content'], ['featured' => true])
      @endif

      <div class="post-list" data-post-list>
        @while(have_posts())
          @php(the_post())
          @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
        @endwhile
      </div>

      <nav class="posts-nav" aria-label="{{ __('Posts', 'sage') }}">
        {!! get_the_posts_navigation() !!}
      </nav>
    @endif

    @include('partials.write-subscribe', compact('writeId'))

    @if ($devto)
      <h2 class="display-title is-section write-devto-h">{{ \App\field('write_devto_h2', __('Also on DEV.to', 'sage'), $writeId) }}</h2>
      <div class="dev-cards">
        @foreach ($devto as $d)
          <a class="dev-card" href="{{ esc_url($d['url']) }}" rel="noopener" target="_blank">
            <p class="eyebrow">DEV.to</p>
            <h3>{{ $d['title'] }}</h3>
            @if (! empty($d['ex']))
              <p>{{ $d['ex'] }}</p>
            @endif
            @if (! empty($d['date']) && strtotime($d['date']))
              <p class="post-meta"><time datetime="{{ esc_attr(gmdate('c', strtotime($d['date']))) }}">{{ wp_date(get_option('date_format'), strtotime($d['date'])) }}</time></p>
            @endif
            <span class="post-card-more">{{ __('Read on DEV.to', 'sage') }} <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span> <span aria-hidden="true">&rarr;</span></span>
          </a>
        @endforeach
      </div>
    @endif

    <p class="write-follow">{{ \App\field('write_follow', __('Follow along:', 'sage'), $writeId) }}</p>
    @include('partials.social', ['labeled' => true])
  </div>
@endsection
