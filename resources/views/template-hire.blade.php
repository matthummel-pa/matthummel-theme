{{--
  Template Name: Hire
--}}
@extends('layouts.app')

@php
  $gh = \App\Github::fetchUser(\App\mh_github_login());

  $goodFor = [
    ['Shops and local businesses', 'home'],
    ['Agencies with client overflow', 'users'],
    ['Developers needing a plugin or integration', 'code'],
    ['Full-time and contract roles', 'briefcase'],
  ];

  $handoff = [
    ['Domain', 'Registered in your name. I have no ongoing access.'],
    ['Hosting account', 'Transferred to you. You control the login.'],
    ['Code', 'GitHub repo with full commit history.'],
    ['Database', 'Full export and credentials.'],
    ['Admin guide', 'Plain-language walkthrough of every editable area.'],
    ['Post-launch support', 'Reachable for questions. No contract required.'],
  ];
@endphp

@section('content')

{{-- ── HERO ────────────────────────────────────────────── --}}
@component('partials.page-hero')
  <p class="eyebrow">Hire me</p>
  <h1 class="display-title is-hero">Open for new work.</h1>
  <p class="lead">I'm available for WordPress projects right now — site builds, plugins, agency overflow, and Power Platform work. A short note is enough to start.</p>
  @if (! empty($gh['hireable']))
    <p class="hire-avail">
      <span class="h-badge__dot" aria-hidden="true"></span>
      Currently available — reply within a day
    </p>
  @endif
  <div class="hire-good-for">
    <span class="hire-good-for__label">Good for:</span>
    @foreach ($goodFor as [$label, $icon])
      <span class="hire-good-for__pill">
        {!! \App\mh_svg_icon($icon, 13) !!} {{ $label }}
      </span>
    @endforeach
  </div>
  <div class="svc-hero-actions" style="margin-top:1.25rem">
    <a class="btn" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!} Say hello
    </a>
    <a class="h-text-arrow" href="{{ home_url('/services/') }}">
      Full services detail <span aria-hidden="true">→</span>
    </a>
  </div>
@endcomponent

{{-- ── WHAT I NEED FROM YOU ──────────────────────────── --}}
<section class="pf-section" aria-labelledby="hire-need-heading">
  <div class="container wide">
    <div class="hire-need-layout">
      <div class="hire-need-copy">
        <p class="eyebrow">To get started</p>
        <h2 id="hire-need-heading" class="display-title is-section">What I need from you.</h2>
        <p>You don't need a finished spec. A short description of the problem is enough. Here's what helps:</p>
        <ul class="hire-need-list">
          <li>Who the site or app is for</li>
          <li>What it needs to do</li>
          <li>Any existing sites or references you like</li>
          <li>A rough sense of your timeline</li>
        </ul>
        <p style="margin-top:1rem;font-size:.92rem;color:var(--color-text-secondary)">That's it. I'll follow up with clarifying questions or an honest note if I'm not the right fit.</p>
      </div>
      <div class="hire-need-cta">
        <h3 class="hire-need-cta__heading">Ready to write?</h3>
        <p class="hire-need-cta__body">Use the contact form — a paragraph is plenty. I reply within a day.</p>
        <a class="btn" href="{{ home_url('/contact/') }}" style="width:100%;justify-content:center;margin-top:.85rem">
          {!! \App\mh_svg_icon('mail', 15) !!} Say hello
        </a>
        <p class="hire-need-cta__note">Want the full breakdown first? <a href="{{ home_url('/services/') }}">Read services →</a></p>
      </div>
    </div>
  </div>
</section>

{{-- ── FOUR STEPS ─────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="hire-process-heading">
  <div class="container wide">
    <p class="eyebrow">The process</p>
    <h2 id="hire-process-heading" class="display-title is-section">From hello to handoff.</h2>
    <div class="hire-steps">
      @foreach ([
        ['Write', 'A few sentences. No spec, no pitch deck.', '1–2 days'],
        ['Scope', 'I send a written list of work, timeline, and what\'s out of scope.', '2–4 days'],
        ['Build', 'Staged previews on real pages. Changes before launch.', '1–4 weeks'],
        ['Yours', 'Domain, hosting, code, and a plain-language admin guide — all transferred.', 'Handoff day'],
      ] as $i => [$title, $body, $time])
        <div class="hire-step">
          <div class="hire-step__head">
            <span class="hire-step__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
            <span class="hire-step__time">{!! \App\mh_svg_icon('calendar', 13) !!} {{ $time }}</span>
          </div>
          <h3 class="hire-step__title">{{ $title }}</h3>
          <p class="hire-step__body">{{ $body }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ── WHAT YOU LEAVE WITH ────────────────────────────── --}}
<section class="pf-section" aria-labelledby="hire-get-heading">
  <div class="container wide">
    <div class="hire-get-layout">
      <div>
        <p class="eyebrow">At handoff</p>
        <h2 id="hire-get-heading" class="display-title is-section">What you leave with.</h2>
        <p style="color:var(--color-text-secondary);font-size:.95rem;max-width:40ch">No lock-in. No ongoing contracts unless you want one. Everything is transferred on handoff day.</p>
      </div>
      <div class="hire-get-grid">
        @foreach ($handoff as [$item, $desc])
          <div class="hire-get-item">
            <span class="hire-get-item__icon">{!! \App\mh_svg_icon('check', 15) !!}</span>
            <div>
              <strong class="hire-get-item__name">{{ $item }}</strong>
              <p class="hire-get-item__desc">{{ $desc }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

{{-- ── FINAL CTA ───────────────────────────────────────── --}}
<section class="cta-band" aria-labelledby="hire-cta-heading">
  <div class="container wide cta-band-inner">
    <div>
      <p class="eyebrow eyebrow--on-dark">Let's go</p>
      <h2 id="hire-cta-heading" class="display-title is-section">Ready to start?</h2>
      <p>Write a short note about what you're building. I'll reply within a day.</p>
    </div>
    <div style="display:flex;flex-direction:column;gap:.75rem;align-items:flex-end;flex-shrink:0">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!} Say hello
      </a>
      <a class="about-text-link" href="{{ home_url('/services/') }}" style="color:#9ca3af">Full services page →</a>
    </div>
  </div>
</section>

@endsection
