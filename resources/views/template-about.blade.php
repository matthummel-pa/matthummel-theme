{{--
  Template Name: About
--}}
@extends('layouts.app')

@php
  $gh = \App\Github::fetchUser(\App\mh_github_login());
  $ghUrl = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $ghBlog = \App\mh_github_blog_url($gh);
  $aboutLede = __('I’m Matt. I live in Gettysburg, Pennsylvania. I write about the web, share code, and sometimes help a shop, a team, or an agency with a site or an app.', 'sage');
  if (! empty($gh['location']) && ! empty($gh['bio'])) {
      $aboutLede = sprintf(
          /* translators: 1: GitHub location, 2: GitHub bio */
          __('I’m Matt. I live in %1$s. On GitHub: %2$s I write about the web, share code, and sometimes help a shop, a team, or an agency with a site or an app.', 'sage'),
          $gh['location'],
          rtrim((string) $gh['bio'], '.').'.'
      );
  }
  $aboutGh = __('On GitHub I keep it short: full-stack web developer with WordPress and Power Platform. That’s still true.', 'sage');
  if (! empty($gh['bio'])) {
      $aboutGh = $gh['bio'];
      if (! empty($gh['created'])) {
          $aboutGh .= ' '.sprintf(
              /* translators: %s: year the GitHub account was created */
              __('On GitHub since %s.', 'sage'),
              $gh['created']
          );
      }
      if (! empty($gh['public_repos'])) {
          $aboutGh .= ' '.sprintf(
              /* translators: %s: public repository count */
              __('%s public repositories.', 'sage'),
              number_format_i18n((int) $gh['public_repos'])
          );
      }
  }
@endphp

@section('content')
  <header class="page-header container wide hero-intro">
    @include('partials.profile-photo', ['size' => 96, 'class' => 'profile-photo profile-photo--hero', 'eager' => true])
    <div>
      <p class="eyebrow">{{ \App\field('about_kicker', __('About', 'sage')) }}</p>
      <h1 class="display-title is-hero">{{ \App\field('about_h1', __('Glad you’re here.', 'sage')) }}</h1>
      <p class="lead">{{ \App\field('about_lede', $aboutLede) }}</p>
    </div>
  </header>

  @include('partials.audience')

  <section class="pf-section pf-section--alt">
    <div class="container wide split about-split">
      <div>
        <h2 class="display-title is-section">{{ \App\field('about_story_h2', __('How I got here', 'sage')) }}</h2>
        <p>{{ \App\field('about_p1', __('I started by building WordPress sites for higher-ed marketing teams. That taught me to care about what people need, not just the stack.', 'sage')) }}</p>
        <p>{{ \App\field('about_p2', __('Later I learned full-stack work and Microsoft 365. Day to day I still mix a public site with the quieter tools a team uses behind it.', 'sage')) }}</p>
      </div>
      <div class="about-gh">
        <p class="eyebrow">{{ __('GitHub', 'sage') }}</p>
        <p>{{ \App\field('about_p3', $aboutGh) }}</p>
        <p>
          <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">{{ __('GitHub profile', 'sage') }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
          @if (! empty($gh['hireable']))
            · {{ __('Available for hire', 'sage') }}
          @endif
        </p>
      </div>
    </div>
  </section>

  <section class="pf-section">
    <div class="container wide">
      <h2 class="display-title is-section">{{ \App\field('about_places_h2', __('Two places I publish', 'sage')) }}</h2>
      <div class="pf-grid">
        @foreach (\App\field_rows('about_places', [
          ['title' => __('matthummel.com', 'sage'), 'text' => __('This site. Writing, public code, snippets, and a quiet way to say hello. Built so you can learn or copy without a sales funnel.', 'sage'), 'url' => ''],
          ['title' => __('Ridges & Valleys', 'sage'), 'text' => __('A Gettysburg studio for Adams County shops, inns, and tours. You own the domain and the hosting.', 'sage'), 'url' => $ghBlog],
        ]) as $place)
          <article class="pf-card">
            <h3>
              @if (! empty($place['url']))
                <a href="{{ esc_url($place['url']) }}" rel="noopener" target="_blank">{{ $place['title'] }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
              @else
                {{ $place['title'] }}
              @endif
            </h3>
            <p>{{ $place['text'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="pf-section pf-section--alt">
    <div class="container wide">
      <h2 class="display-title is-section">{{ \App\field('about_values_h2', __('How I like to work', 'sage')) }}</h2>
      <ol class="value-grid">
        @foreach (\App\field_lines('about_values', [
          __('Plain words, about a 6–8 grade reading level.', 'sage'),
          __('Accessible pages as a default, not a later patch.', 'sage'),
          __('You can use a keyboard, a phone, or dark mode.', 'sage'),
          __('I use AI as a helper. I still read every line before it ships.', 'sage'),
        ]) as $item)
          <li>{{ $item }}</li>
        @endforeach
      </ol>
      <p>{!! \App\field_html('about_links', __('<a href="/now/">What I’m doing now</a> · <a href="/contact/">Say hello</a>', 'sage')) !!}</p>
    </div>
  </section>
@endsection
