{{--
  Template Name: Services
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container">
    <h1 class="display-title is-hero">Help when you need it.</h1>
    <p class="lead">Most of this site is free to read and copy. If you run a business, a nonprofit, or a small team and you need a site or an internal app, I can sometimes help. I have a full-time Power Platform job, so I take a few extra projects at a time.</p>
  </header>

  <section class="pf-section">
    <div class="container">
      <div class="svc-list">
        <article class="svc-item">
          <h2>WordPress sites</h2>
          <p>New sites, rescues, and Sage 11 themes. Fast pages you can edit, with a handoff you understand.</p>
        </article>
        <article class="svc-item">
          <h2>Power Platform</h2>
          <p>Power Apps, Power Automate, and SharePoint. Replace paper and long email chains with an app the team can run.</p>
        </article>
        <article class="svc-item">
          <h2>Small apps</h2>
          <p>When WordPress is the wrong tool. React, APIs, and data — like Keepary on GitHub, which you can read.</p>
        </article>
        <article class="svc-item">
          <h2>Cleanup</h2>
          <p>Speed, accessibility, and search. A short list of fixes, explained in plain words.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="pf-section">
    <div class="container measure">
      <h2 class="display-title is-section">A fair picture</h2>
      <p>I don’t run ads or social accounts for clients. Local Gettysburg marketing lives at <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a>. Here, sharing comes first. If a build would help, <a href="{{ home_url('/contact/') }}">write a short note</a> and tell me what you’re trying to do.</p>
    </div>
  </section>
@endsection
