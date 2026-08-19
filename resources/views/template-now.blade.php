{{--
  Template Name: Now
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container">
    <h1 class="display-title is-hero">What I’m doing now.</h1>
    <p class="lead">A short, honest list. Updated with this redesign.</p>
  </header>
  <section class="pf-section">
    <div class="container measure">
      <ul>
        <li>Full-time Power Platform work.</li>
        <li>Raising kids in Gettysburg. Nights and weekends are scarce, so I ship with a tight scope.</li>
        <li>Rebuilding this portfolio as a Sage 11 theme: blue, gray, accessible, easy to read.</li>
        <li>Sharing notes on this blog, DEV.to, Bluesky, and Reddit.</li>
        <li>Taking a few WordPress and Power Platform side jobs when the fit is clear.</li>
      </ul>
      <p><a href="{{ home_url('/contact/') }}">Want to work together?</a></p>
    </div>
  </section>
@endsection
