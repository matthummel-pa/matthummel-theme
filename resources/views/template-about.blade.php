{{--
  Template Name: About
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container">
    <h1 class="display-title is-hero">Glad you’re here.</h1>
    <p class="lead">I’m Matt. I live in Gettysburg, PA. I write about the web, share code, and sometimes help a shop or a team with a site or an app. Plain language. Pages that are easy to use.</p>
  </header>

  <section class="pf-section">
    <div class="container measure">
      <h2 class="display-title is-section">How I got here</h2>
      <p>I started by building WordPress sites for higher-ed marketing teams. That taught me to care about what people need, not just the stack.</p>
      <p>Later I added full-stack work and Microsoft 365. Now I can help with a public site and the internal app that runs the work behind it.</p>
      <p>On GitHub I keep it short: full-stack web developer with WordPress and Power Platform. That’s still true.</p>
    </div>
  </section>

  <section class="pf-section">
    <div class="container">
      <h2 class="display-title is-section">Two places I publish</h2>
      <div class="pf-grid">
        <article class="pf-card">
          <h3>matthummel.com</h3>
          <p>This site. Writing, public code, snippets, and a quiet way to say hello. Built so you can learn or copy without a sales funnel.</p>
        </article>
        <article class="pf-card">
          <h3>Ridges &amp; Valleys</h3>
          <p>A Gettysburg studio for Adams County shops, inns, and tours. You own the domain and the hosting. <a href="https://ridgesandvalleys.com">ridgesandvalleys.com</a></p>
        </article>
      </div>
    </div>
  </section>

  <section class="pf-section">
    <div class="container measure">
      <h2 class="display-title is-section">How I like to work</h2>
      <ul>
        <li>Plain words, about a 6–8 grade reading level.</li>
        <li>Accessible pages as a default, not a later patch.</li>
        <li>You can use a keyboard, a phone, or dark mode.</li>
        <li>I use AI as a helper. I still read every line before it ships.</li>
      </ul>
      <p><a href="{{ home_url('/now/') }}">What I’m doing now</a> · <a href="{{ home_url('/contact/') }}">Say hello</a></p>
    </div>
  </section>
@endsection
