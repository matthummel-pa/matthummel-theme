{{--
  Template Name: Services
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container">
    <p class="eyebrow">Services</p>
    <h1 class="display-title is-hero">Small jobs. Clear scope. Honest fit.</h1>
    <p class="lead">I work full-time as a Power Platform developer. I take a few side projects. If it is not a fit, I will say so.</p>
  </header>

  <section class="pf-section pf-section--alt">
    <div class="container">
      <div class="pf-grid">
        <article class="pf-card">
          <h2>WordPress sites</h2>
          <p>New sites, rescues, and Sage 11 themes. Fast pages, WCAG 2.2 AA, and a handoff you can edit.</p>
        </article>
        <article class="pf-card">
          <h2>Power Platform</h2>
          <p>Power Apps, Power Automate, and SharePoint. Replace paper and email chains with an app the team can run.</p>
        </article>
        <article class="pf-card">
          <h2>Full-stack apps</h2>
          <p>When WordPress is the wrong tool. React, APIs, and data layers — like Keepary on GitHub.</p>
        </article>
        <article class="pf-card">
          <h2>Fixes that matter</h2>
          <p>Speed, accessibility, and SEO cleanup. A punch list, not a mystery rewrite.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="pf-section">
    <div class="container measure">
      <h2 class="display-title is-section">What I do not take</h2>
      <p>Paid ads, full social management, or giant content retainers. Local Gettysburg marketing lives at Ridges &amp; Valleys. This site is for code, writing, and a few build jobs.</p>
      <a class="btn" href="{{ home_url('/contact/') }}">Ask about a project</a>
    </div>
  </section>
@endsection
