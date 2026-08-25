{{--
  Template Name: Hire
--}}
@extends('layouts.app')

@php
  $gh = \App\Github::fetchUser(\App\mh_github_login());

  $fits = [
    [
      'icon'  => 'home',
      'title' => 'Shops and local businesses',
      'body'  => 'You need a WordPress site you own and can update yourself. Based in Gettysburg or anywhere remote.',
      'items' => [
        'New site build or redesign',
        'Contact form, booking, or menu',
        'Pages you can edit in wp-admin without a developer',
        'Full handoff — domain, hosting, code, all yours',
      ],
    ],
    [
      'icon'  => 'users',
      'title' => 'Agencies with overflow',
      'body'  => 'You have a client project that needs a WordPress developer. You keep the relationship; I stay in the background.',
      'items' => [
        'WordPress site or plugin build',
        'Fixed scope agreed upfront',
        'Clean handoff to your team',
        'NDA available',
      ],
    ],
    [
      'icon'  => 'code',
      'title' => 'Developers and teams',
      'body'  => 'You need a focused plugin, a REST API, or a second pair of hands on a specific problem.',
      'items' => [
        'Custom WordPress plugin',
        'REST API or data integration',
        'Power Apps / Power Automate',
        'Code you can read and maintain',
      ],
    ],
  ];

  $process = [
    ['Write', 'A few sentences about what you need. No spec, no pitch deck required.', '1–2 days'],
    ['Scope', 'I send a plain list of work, a timeline, and what\'s explicitly out of scope.', '2–4 days'],
    ['Build', 'Staged previews on real pages. Changes before launch, not after.', '1–4 weeks'],
    ['Yours', 'Everything transferred — domain, hosting, code. Handoff guide included.', 'Handoff day'],
  ];
@endphp

@section('content')

{{-- HERO --}}
@component('partials.page-hero')
  <p class="eyebrow">Hire me</p>
  <h1 class="display-title is-hero">Open for new work.</h1>
  <p class="lead">I'm available for WordPress projects right now — site builds, plugins, and overflow development. A short note about what you need is enough to get started.</p>
  @if (! empty($gh['hireable']))
    <p class="hire-avail">
      <span class="h-badge__dot" aria-hidden="true"></span>
      Currently available — reply within a day
    </p>
  @endif
  <div class="svc-hero-actions" style="margin-top:1.25rem">
    <a class="btn" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!} Say hello
    </a>
    <a class="h-text-arrow" href="{{ home_url('/projects/') }}">
      See example sites <span aria-hidden="true">→</span>
    </a>
  </div>
@endcomponent

{{-- WHO I HELP --}}
<section class="pf-section pf-section--alt" aria-labelledby="hire-who-heading">
  <div class="container wide">
    <p class="eyebrow">Who I work with</p>
    <h2 id="hire-who-heading" class="display-title is-section">Who this is for.</h2>
    <div class="hire-cards">
      @foreach ($fits as $fit)
        <div class="hire-card">
          <div class="hire-card__icon">{!! \App\mh_svg_icon($fit['icon'], 22) !!}</div>
          <h3 class="hire-card__title">{{ $fit['title'] }}</h3>
          <p class="hire-card__body">{{ $fit['body'] }}</p>
          <ul class="hire-card__list">
            @foreach ($fit['items'] as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- WHAT I NEED FROM YOU --}}
<section class="pf-section" aria-labelledby="hire-need-heading">
  <div class="container wide">
    <div class="hire-need-layout">
      <div class="hire-need-copy">
        <p class="eyebrow">To get started</p>
        <h2 id="hire-need-heading" class="display-title is-section">What I need from you.</h2>
        <p>You don't need a finished spec. A short description of the problem is enough to start a conversation. Here's what helps:</p>
        <ul class="hire-need-list">
          <li>Who the site or app is for</li>
          <li>What it needs to do</li>
          <li>Any existing sites, designs, or references you like</li>
          <li>A rough sense of your timeline</li>
        </ul>
        <p style="margin-top:1rem;font-size:.95rem;color:var(--color-text-secondary)">That's it. I'll follow up with clarifying questions or an honest answer if I'm not the right fit.</p>
      </div>
      <div class="hire-need-cta">
        <h3 class="hire-need-cta__heading">Ready to write?</h3>
        <p class="hire-need-cta__body">Use the contact form — a paragraph is plenty. I reply within a day.</p>
        <a class="btn" href="{{ home_url('/contact/') }}" style="width:100%;justify-content:center;margin-top:.85rem">
          {!! \App\mh_svg_icon('mail', 15) !!} Say hello
        </a>
        <p class="hire-need-cta__note">Or email — check the <a href="{{ home_url('/about/') }}">About page</a> for my address.</p>
      </div>
    </div>
  </div>
</section>

{{-- HOW IT WORKS --}}
<section class="pf-section pf-section--alt" aria-labelledby="hire-process-heading">
  <div class="container wide">
    <p class="eyebrow">The process</p>
    <h2 id="hire-process-heading" class="display-title is-section">Four steps from hello to handoff.</h2>
    <div class="hire-steps">
      @foreach ($process as $i => [$title, $body, $time])
        <div class="hire-step">
          <div class="hire-step__head">
            <span class="hire-step__num" aria-hidden="true">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
            <span class="hire-step__time">{!! \App\mh_svg_icon('calendar', 13) !!} {{ $time }}</span>
          </div>
          <h3 class="hire-step__title">{{ $title }}</h3>
          <p class="hire-step__body">{{ $body }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- WHAT YOU GET --}}
<section class="pf-section" aria-labelledby="hire-get-heading">
  <div class="container wide">
    <div class="hire-get-layout">
      <div>
        <p class="eyebrow">At handoff</p>
        <h2 id="hire-get-heading" class="display-title is-section">What you leave with.</h2>
        <p style="color:var(--color-text-secondary);font-size:.95rem;max-width:44ch">No lock-in. No ongoing contracts unless you want one. You get everything.</p>
      </div>
      <div class="hire-get-grid">
        @foreach ([
          ['Domain', 'Registered in your name. I have no ongoing access.'],
          ['Hosting account', 'Transferred to you. You control the login.'],
          ['Code', 'GitHub repo with full commit history. Any developer can pick it up.'],
          ['Database', 'A full export plus credentials. Your content stays yours.'],
          ['Admin guide', 'Plain-language walkthrough of every editable area. Written for you, not developers.'],
          ['Post-launch support', 'Reachable for questions after handoff. Not on a contract — just available.'],
        ] as [$item, $desc])
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

{{-- FINAL CTA --}}
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
      <a class="about-text-link" href="{{ home_url('/services/') }}" style="color:#9ca3af">Full services detail →</a>
    </div>
  </div>
</section>

@endsection
