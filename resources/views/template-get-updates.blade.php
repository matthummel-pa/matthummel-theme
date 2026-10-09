{{--
  Template Name: Get Updates
--}}
@extends('layouts.app')

@section('content')
@php
  $pageId = (int) get_the_ID();
  $isPrefs = is_page('email-preferences');
  $isUnsub = is_page('unsubscribe');
  $heroKicker = $isUnsub || $isPrefs
    ? __('Email', 'sage')
    : \App\field('upd_kicker', __('Get updates', 'sage'), $pageId);
  $heroTitle = $isUnsub
    ? __('Unsubscribe', 'sage')
    : ($isPrefs ? __('Manage preferences', 'sage') : \App\field('upd_h1', __('Notes when I publish.', 'sage'), $pageId));
  $heroLead = $isUnsub
    ? __('Use the link in an email, then confirm. I keep the address on this site.', 'sage')
    : ($isPrefs
      ? __('Use the link in an email to change your name or unsubscribe. I keep the address on this site.', 'sage')
      : \App\field('upd_lede', __('Occasional notes on WordPress and new posts. I keep your address on this site. No newsletter service.', 'sage'), $pageId));
  $heroLabel = $isUnsub ? __('Unsubscribe', 'sage') : ($isPrefs ? __('Preferences', 'sage') : __('Sign up', 'sage'));
@endphp

@component('partials.page-hero', ['extra' => 'updates-hero'])
  <p class="eyebrow">{{ $heroKicker }}</p>
  <h1 class="display-title is-hero">
    {{ $heroTitle }}
  </h1>
  <p class="lead">
    {{ $heroLead }}
  </p>
@endcomponent

<section class="updates-main" aria-label="{{ $heroLabel }}">
  <div class="container wide updates-wrap">
    @if (shortcode_exists('mhn_updates'))
      @php(the_content())
    @else
      <p class="lead">{{ __('Use the footer form on this page. I keep the address on this site.', 'sage') }}</p>
    @endif
  </div>
</section>
@endsection
