{{--
  Template Name: Now
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container">
    <h1 class="display-title is-hero">What I’m doing now.</h1>
    <p class="lead">A short list of where my time is going right now.</p>
  </header>
  <section class="pf-section">
    <div class="container measure">
      <ul>
        <li>Full-time Power Platform work.</li>
        <li>Raising kids in Gettysburg. Nights and weekends are scarce, so I ship with a tight scope.</li>
        <li>Rebuilding this portfolio as a Sage 11 theme: blue, gray, accessible, easy to read.</li>
        <li>Sharing notes and snippets on this blog, DEV.to, Bluesky, and Reddit.</li>
        <li>Helping with a few WordPress and Power Platform builds when I have room.</li>
      </ul>
      <p><a href="{{ home_url('/contact/') }}">Say hello</a></p>
    </div>
  </section>
@endsection
