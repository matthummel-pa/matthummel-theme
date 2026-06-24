@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @php
      $owner   = get_post_meta(get_the_ID(), '_mh_gh_owner', true) ?: apply_filters('matthummel/github_owner', 'matthummel-pa');
      $repo    = get_post_meta(get_the_ID(), '_mh_gh_repo', true) ?: get_post_field('post_name');
      $eyebrow = get_post_meta(get_the_ID(), '_mh_eyebrow', true);
      $demo    = get_post_meta(get_the_ID(), '_mh_demo_url', true);
      $gh      = "https://github.com/{$owner}/{$repo}";
      $data    = \App\Github::fetch($owner, $repo);
      $images  = get_attached_media('image', get_the_ID());
    @endphp

    <article @php(post_class('project-single'))>
      <header class="project-hero container">
        @if ($eyebrow)
          <p class="eyebrow">{{ $eyebrow }}</p>
        @endif

        <h1 class="display-title is-hero">{{ get_the_title() }}</h1>

        @if (! empty($data['desc']))
          <p class="lead">{{ $data['desc'] }}</p>
        @endif

        <div class="btn-row">
          @if ($demo)
            <a class="btn" href="{{ esc_url($demo) }}">{{ __('Live demo', 'matthummel') }}</a>
          @endif
          <a class="btn {{ $demo ? 'btn-outline' : '' }}" href="{{ esc_url($gh) }}">{{ __('View on GitHub', 'matthummel') }}</a>
          <a class="btn btn-outline" href="{{ esc_url($gh . '#readme') }}">{{ __('README', 'matthummel') }}</a>
        </div>

        {!! \App\Github::render($owner, $repo, ['stats']) !!}
      </header>

      @if ($images)
        <hr class="rule">
        <h2 class="display-title is-section">{{ __('A look inside', 'matthummel') }}</h2>
        <div class="shot-grid">
          @foreach ($images as $img)
            {!! wp_get_attachment_image($img->ID, 'large', false, ['loading' => 'lazy']) !!}
          @endforeach
        </div>
      @endif

      @if (! empty($data['intro']))
        <hr class="rule">
        <h2 class="display-title is-section">{{ __('About this project', 'matthummel') }}</h2>
        {!! \App\Github::render($owner, $repo, ['intro']) !!}
      @endif

      @php($body = trim(get_the_content()))
      @if ($body)
        <hr class="rule">
        <div class="entry-content measure">@php(the_content())</div>
      @endif
    </article>
  @endwhile
@endsection
