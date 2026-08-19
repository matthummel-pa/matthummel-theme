{{--
  Template Name: About
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container">
    <h1 class="display-title is-hero">A developer who cares how things feel to use.</h1>
    <p class="lead">I’m Matt. I live in Gettysburg, PA. I have spent 15+ years building for the web. I write in plain language. I ship accessible sites. I work full-time on the Microsoft Power Platform and I still like making WordPress and full-stack apps on the side.</p>
  </header>

  <section class="pf-section">
    <div class="container measure">
      <h2 class="display-title is-section">How I got here</h2>
      <p>I started by building WordPress sites for higher-ed marketing teams. That taught me to care about what people need, not just the stack.</p>
      <p>Then I went deeper on two fronts: full-stack web work, and Microsoft 365. Today those sit together. I can design a public site and also build the internal app that runs the work behind it.</p>
      <p>My GitHub bio is short on purpose: full-stack web developer with WordPress and Power Platform. That’s the job.</p>
    </div>
  </section>

  <section class="pf-section">
    <div class="container">
      <h2 class="display-title is-section">Two shops, one person</h2>
      <div class="pf-grid">
        <article class="pf-card">
          <h3>matthummel.com</h3>
          <p>This site. A quiet developer portfolio. Writing, code, and a few side jobs. Built so I can share repos and notes without the noise.</p>
        </article>
        <article class="pf-card">
          <h3>Ridges &amp; Valleys</h3>
          <p>My Gettysburg studio. Fast, accessible WordPress sites for Adams County shops, inns, and tours. You own the domain. You own the hosting. <a href="https://ridgesandvalleys.com">ridgesandvalleys.com</a></p>
        </article>
      </div>
    </div>
  </section>

  <section class="pf-section">
    <div class="container measure">
      <h2 class="display-title is-section">How I work</h2>
      <ul>
        <li>Plain words, about a 6–8 grade reading level.</li>
        <li>WCAG 2.2 AA as a default, not a later patch.</li>
        <li>Mobile first. Keyboard first. Dark mode that you control.</li>
        <li>I use AI as a helper. I still read every line before it ships.</li>
      </ul>
      <p><a href="{{ home_url('/now/') }}">What I’m doing now</a> · <a href="{{ home_url('/contact/') }}">Contact</a></p>
    </div>
  </section>
@endsection
