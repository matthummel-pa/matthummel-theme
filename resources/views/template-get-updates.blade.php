{{--
  Template Name: Get Updates
--}}
@extends('layouts.app')

@section('content')
@php
  $pageId = (int) get_the_ID();
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
  </div>
</section>
@endsection
