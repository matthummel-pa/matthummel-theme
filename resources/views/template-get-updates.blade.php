{{--
  Template Name: Get Updates
--}}
@extends('layouts.app')

@section('content')
@php
  $pageId = (int) get_the_ID();
  // The Reach form is a plugin block. If the plugin is off, or the form was removed from the page, the page would be empty.
  $hasForm = \WP_Block_Type_Registry::get_instance()->is_registered('hostinger-reach/subscription')
    && str_contains((string) get_post_field('post_content', $pageId), 'wp:hostinger-reach/');
@endphp

@component('partials.page-hero', ['extra' => 'updates-hero'])
  <p class="eyebrow">{{ \App\field('upd_kicker', __('Get updates', 'sage'), $pageId) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('upd_h1', __('Notes when I publish', 'sage'), $pageId) }}
  </h1>
  <p class="lead">
    {{ \App\field('upd_lede', __('Occasional notes on WordPress and new posts. Unsubscribe any time from the link in each one.', 'sage'), $pageId) }}
  </p>
@endcomponent

{{-- The page body holds the Hostinger Reach form block (classic Reach form). Edit it in the page editor. --}}
<section class="updates-main" aria-label="{{ __('Sign up', 'sage') }}">
  <div class="container wide updates-wrap">
    @php(the_content())
    @unless ($hasForm)
      <div class="updates-fallback">
        <p>{{ __('The email signup is not working right now. Say hello and I will add you to the list by hand, or follow the RSS feed.', 'sage') }}</p>
        <p class="updates-fallback__actions">
          <a class="btn" href="{{ esc_url(home_url('/contact/')) }}">{{ __('Say hello', 'sage') }}</a>
          <a class="btn btn-outline" href="{{ esc_url(home_url('/feed/')) }}" rel="alternate" type="application/rss+xml">{{ __('RSS feed', 'sage') }}</a>
        </p>
      </div>
    @endunless
  </div>
</section>
@endsection
