{{--
  Template Name: Account
  Customer dashboard for theme buyers. Licenses, downloads, and invoices live in the Freemius Customer Portal;
  the page body can hold the embedded portal (Freemius Customer Portal plugin) once it is installed.
--}}
@extends('layouts.app')

@section('content')
@php
  $pageId = (int) get_the_ID();
  $portalUrl = esc_url(\App\field('acct_portal_url', 'https://customers.freemius.com/store/21514/', $pageId));
@endphp

@component('partials.page-hero', ['extra' => 'account-hero'])
  <p class="eyebrow">{{ \App\field('acct_kicker', __('Account', 'sage'), $pageId) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('acct_h1', __('Your licenses and downloads', 'sage'), $pageId) }}
  </h1>
  <p class="lead">
    {{ \App\field('acct_lede', __('Sign in with the email you used at checkout to download the theme, see your license key, move a site, or get an invoice.', 'sage'), $pageId) }}
  </p>
  @if ($portalUrl !== '')
    <p class="concept-hero-actions">
      <a class="btn" href="{{ $portalUrl }}" rel="noopener">
        {!! \App\mh_svg_icon('user', 15) !!}
        {{ \App\field('acct_portal_label', __('Open your account', 'sage'), $pageId) }}
      </a>
    </p>
  @endif
@endcomponent

<section class="project-section" aria-label="{{ __('Account help', 'sage') }}">
  <div class="container wide">
    @php(the_content())
    <p class="project-section__intro">
      {{ \App\field('acct_help', __('Lost your license email, or need to change plans? Write to me and I will sort it out.', 'sage'), $pageId) }}
      <a href="{{ esc_url(home_url('/contact/')) }}">{{ __('Say hello', 'sage') }}</a>
    </p>
  </div>
</section>
@endsection
