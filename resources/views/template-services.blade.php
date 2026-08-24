{{--
  Template Name: Services
--}}
@extends('layouts.app')

@php
  $services = [
    [
      'icon'  => 'wordpress',
      'num'   => '01',
      'title' => 'WordPress sites',
      'short' => 'New builds, redesigns, and landing pages.',
      'body'  => 'A custom Sage / Blade theme built to your specs, with wp-admin edit fields for every content area that matters — no developer needed for day-to-day updates. Mobile-first, fast, accessible, and 100% yours.',
      'gets'  => ['Custom Sage / Blade theme', 'Admin fields you edit yourself', 'Mobile-first, accessible markup', 'Plain-language handoff guide'],
    ],
    [
      'icon'  => 'plugins',
      'num'   => '02',
      'title' => 'WordPress plugins',
      'body'  => 'Small, focused plugins that do one job well. Built with PHP, documented so any developer can read them later, and sized to solve the actual problem — not a general-purpose toolkit you\'ll spend months maintaining.',
      'short' => 'Custom functionality with no bloat.',
      'gets'  => ['Single-purpose, readable code', 'PHPDoc on all public functions', 'Clean uninstall — no leftover data', 'Standard WordPress hooks throughout'],
    ],
    [
      'icon'  => 'code',
      'num'   => '03',
      'title' => 'Other web apps',
      'short' => 'When WordPress isn\'t the right fit.',
      'body'  => 'React front-ends, REST APIs, and data-driven apps. I also take Power Platform work — Power Apps and Power Automate — when a team already lives in Microsoft 365 and it\'s the right tool for the problem.',
      'gets'  => ['React or vanilla JS front-ends', 'REST APIs and data integrations', 'Power Apps / Power Automate', 'GitHub repo with full commit history'],
    ],
    [
      'icon'  => 'users',
      'num'   => '04',
      'title' => 'Agency and overflow work',
      'short' => 'Sub-contracting on your projects.',
      'body'  => 'You keep the client relationship and the billing. I build the WordPress site, plugin, or app. Scope and price agreed between us before anything starts. I can sign an NDA.',
      'gets'  => ['You keep the client relationship', 'Fixed scope and price upfront', 'Clean handoff to your team', 'NDA available if needed'],
    ],
  ];

  $processSteps = [
    [
      'num'    => '01',
      'title'  => 'Write.',
      'timing' => '1–2 days',
      'body'   => 'Tell me who the site is for, what needs fixing, and what a good outcome looks like. A few sentences are enough. No spec, no wireframe, no slides required.',
      'gets'   => 'A quick reply with clarifying questions — or an honest note if I\'m not the right fit.',
    ],
    [
      'num'    => '02',
      'title'  => 'Scope.',
      'timing' => '2–4 days',
      'body'   => 'I send a plain list of work, a rough timeline, a fixed price, and an explicit list of what\'s out of scope. You approve, push back, or ask questions.',
      'gets'   => 'A written scope document. No retainers, no hourly billing, no surprise invoices.',
    ],
    [
      'num'    => '03',
      'title'  => 'Build.',
      'timing' => '1–4 weeks',
      'body'   => 'I build in stages and share previews on real pages — not static mockups. You review something you can click. Changes happen before launch, not after.',
      'gets'   => 'Staged previews, version-controlled code, a shareable review link.',
    ],
    [
      'num'    => '04',
      'title'  => 'Yours.',
      'timing' => 'Handoff day',
      'body'   => 'You get everything: the domain, the hosting account, the database, and the code. I write a plain-language admin guide and stay reachable for questions.',
      'gets'   => 'Full ownership transfer. No ongoing fees. No lock-in.',
    ],
  ];

  $faqs = [
    [
      'q' => 'Do you take WordPress projects for Gettysburg businesses?',
      'a' => 'Yes — Gettysburg shops, inns, tours, and restaurants are exactly who Ridges & Valleys was built for. I also take remote work for agencies and businesses anywhere in the US.',
    ],
    [
      'q' => 'Do you work with agencies on client projects?',
      'a' => 'Yes. I\'ve sub-contracted on agency projects before — you keep the client relationship and the billing, I build the WordPress site or plugin. Scope and rate are agreed before anything starts. I can sign an NDA if needed.',
    ],
    [
      'q' => 'How do you price projects?',
      'a' => 'Fixed price, not hourly. After the scope conversation I send a written number. That number doesn\'t change unless the scope does, and if the scope changes we talk before I build more. No surprise invoices.',
    ],
    [
      'q' => 'How long does a WordPress site take to build?',
      'a' => 'A simple site with a few pages and a contact form: two to three weeks. Something with custom fields, a booking system, or filtering: four to six weeks. I give a realistic estimate in scope, not an optimistic one.',
    ],
    [
      'q' => 'What does "you own it" actually mean?',
      'a' => 'The domain is registered in your name. The hosting account is yours. The database and all the files belong to you. After handoff I have no access unless you invite me back. You can hand everything to another developer and they\'ll have what they need.',
    ],
    [
      'q' => 'Can I edit the site myself after you build it?',
      'a' => 'That\'s the goal. I build wp-admin edit fields for every content area that needs to change — text, images, lists, staff bios. Before launch I document anything that isn\'t obvious in plain language.',
    ],
    [
      'q' => 'Do you do design, or just development?',
      'a' => 'Development. I can work from your design, a reference site, or a clear written brief. For original visual design I\'ll refer you to someone who does it well rather than guess.',
    ],
    [
      'q' => 'What kinds of projects are not a good fit?',
      'a' => 'Ongoing social media or ad management. Large enterprise e-commerce built from scratch with no existing design. Projects where no one can make decisions — I need a clear point of contact. If I\'m not the right fit I\'ll say so early.',
    ],
  ];
@endphp

@section('content')

{{-- FAQ schema — helps Google show FAQ rich results --}}
@php
  $faqSchema = array_map(fn($f) => [
    '@type' => 'Question',
    'name'  => $f['q'],
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
  ], $faqs);
  $faqJsonLd = json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqSchema], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
<script type="application/ld+json">{!! $faqJsonLd !!}</script>

{{-- HERO --}}
@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('svc_kicker', __('WordPress developer · Gettysburg, PA', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('svc_h1', __('Custom WordPress sites and plugins for shops, agencies, and developers.', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('svc_lede', __('I build WordPress sites, plugins, and web apps in Gettysburg and remotely. Fixed price, clear scope, no lock-in. You own everything — domain, hosting, code — at handoff.', 'sage')) }}
  </p>
  <p class="about-hero-links" style="margin-top:1rem">
    <a href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 15) !!} Say hello</a>
    <a href="{{ home_url('/projects/') }}">{!! \App\mh_svg_icon('globe', 15) !!} Example sites</a>
    <a href="{{ home_url('/about/') }}">{!! \App\mh_svg_icon('user', 15) !!} About me</a>
  </p>
@endcomponent

{{-- SERVICES --}}
<section class="pf-section" aria-labelledby="svc-ways-heading">
  <div class="container wide">
    <p class="eyebrow">What I build</p>
    <h2 id="svc-ways-heading" class="display-title is-section">
      {{ \App\field('svc_ways_h2', __('Four ways to work together.', 'sage')) }}
    </h2>
    <div class="svc-cards">
      @foreach ($services as $svc)
        <article class="svc-v2-card">
          <div class="svc-v2-card__head">
            <div class="svc-v2-card__icon">{!! \App\mh_svg_icon($svc['icon'], 22) !!}</div>
            <span class="svc-v2-card__num" aria-hidden="true">{{ $svc['num'] }}</span>
          </div>
          <h3 class="svc-v2-card__title">{{ $svc['title'] }}</h3>
          <p class="svc-v2-card__short">{{ $svc['short'] }}</p>
          <p class="svc-v2-card__body">{{ $svc['body'] }}</p>
          <ul class="svc-v2-card__gets">
            @foreach ($svc['gets'] as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        </article>
      @endforeach
    </div>
    <p class="svc-note">
      Not sure which fits your project? <a href="{{ home_url('/contact/') }}">Write a short note</a> describing the problem — I'll help you figure out the right shape.
    </p>
  </div>
</section>

{{-- PROCESS --}}
<section class="pf-section pf-section--alt" aria-labelledby="svc-process-heading">
  <div class="container wide">
    <p class="eyebrow">How it works</p>
    <h2 id="svc-process-heading" class="display-title is-section">
      {{ \App\field('svc_process_h2', __('How a project goes.', 'sage')) }}
    </h2>
    <p class="sec-intro" style="margin-bottom:2.25rem">Four steps. Fixed price. You own everything at the end.</p>
    <div class="svc-process">
      @foreach ($processSteps as $step)
        <div class="svc-process__step">
          <div class="svc-process__step-head">
            <span class="svc-process__num" aria-hidden="true">{{ $step['num'] }}</span>
            <span class="svc-process__timing">
              {!! \App\mh_svg_icon('calendar', 13) !!} {{ $step['timing'] }}
            </span>
          </div>
          <h3 class="svc-process__title">{{ $step['title'] }}</h3>
          <p class="svc-process__body">{{ $step['body'] }}</p>
          <p class="svc-process__gets">
            <span class="svc-process__gets-label">You get:</span>
            {{ $step['gets'] }}
          </p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- FAQ --}}
<section class="pf-section" aria-labelledby="svc-faq-heading">
  <div class="container wide svc-faq-layout">
    <div>
      <p class="eyebrow">Questions</p>
      <h2 id="svc-faq-heading" class="display-title is-section">
        {{ \App\field('svc_faq_h2', __('Frequently asked.', 'sage')) }}
      </h2>
      <p class="sec-intro">Real questions from real conversations. If yours isn't here, <a href="{{ home_url('/contact/') }}">just ask</a>.</p>
    </div>
    <div class="faq-list">
      @foreach ($faqs as $i => $faq)
        <details {{ $i === 0 ? 'open' : '' }}>
          <summary>{{ $faq['q'] }}</summary>
          <p>{{ $faq['a'] }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>

{{-- CTA --}}
<section class="cta-band" aria-labelledby="svc-cta-heading">
  <div class="container wide cta-band-inner">
    <div>
      <p class="eyebrow eyebrow--on-dark">Let's work together</p>
      <h2 id="svc-cta-heading" class="display-title is-section">
        {{ \App\field('svc_fair_h2', __('Ready to talk?', 'sage')) }}
      </h2>
      <p>Tell me what you're building or fixing. A paragraph is enough to get started. I reply within a day.</p>
    </div>
    <div style="display:flex;flex-direction:column;gap:.75rem;align-items:flex-end;flex-shrink:0">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!} Say hello
      </a>
      <a class="about-text-link" href="{{ home_url('/projects/') }}" style="color:#9ca3af">
        See example sites →
      </a>
    </div>
  </div>
</section>

@endsection
