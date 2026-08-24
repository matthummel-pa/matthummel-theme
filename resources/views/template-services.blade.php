{{--
  Template Name: Services
--}}
@extends('layouts.app')

@php
  $ghBlog = \App\mh_github_blog_url(\App\Github::fetchUser(\App\mh_github_login()));

  $services = [
    [
      'icon'    => 'wordpress',
      'num'     => '01',
      'title'   => 'WordPress sites',
      'short'   => 'New builds, redesigns, and care.',
      'body'    => 'Full-site builds using Sage, Blade, and Tailwind. Or a lighter rebuild of what you have. Either way: a site the owner can update without calling a developer, pages that load fast, and code that holds up.',
      'gets'    => ['Custom Sage / Blade theme', 'Admin fields you can edit yourself', 'Mobile-first, accessible markup', 'Handoff guide in plain language'],
    ],
    [
      'icon'    => 'plugins',
      'num'     => '02',
      'title'   => 'WordPress plugins',
      'short'   => 'Custom functionality, nothing extra.',
      'body'    => 'Small, focused plugins that do one job well. Built with PHP, documented so any developer can read them later, and sized to solve the actual problem — not a general-purpose toolkit you\'ll spend months maintaining.',
      'gets'    => ['Single-purpose, readable code', 'PHPDoc on all public functions', 'Clean uninstall — no leftover data', 'Compatible with standard WordPress hooks'],
    ],
    [
      'icon'    => 'code',
      'num'     => '03',
      'title'   => 'Other web apps',
      'short'   => 'When WordPress is the wrong fit.',
      'body'    => 'React front-ends, REST APIs, and data-driven apps. I also do Power Platform work — Power Apps and Power Automate — when a team already lives in Microsoft 365 and it\'s the right tool for the problem.',
      'gets'    => ['React or vanilla JS front-ends', 'REST APIs and integrations', 'Power Apps / Power Automate', 'GitHub repo with full history'],
    ],
    [
      'icon'    => 'users',
      'num'     => '04',
      'title'   => 'Agency and overflow work',
      'short'   => 'Sub-contracting on your client projects.',
      'body'    => 'I\'ve worked as a silent sub on client projects before. You keep the relationship and the billing. I build the WordPress site, plugin, or app. Scope and price agreed between us before anything starts.',
      'gets'    => ['You keep the client relationship', 'Fixed scope and price', 'Clean handoff to your team', 'Can sign an NDA if needed'],
    ],
  ];

  $processSteps = [
    [
      'num'     => '01',
      'title'   => 'Write.',
      'timing'  => '1–2 days',
      'body'    => 'Tell me who the site is for, what\'s broken or missing, and what a good outcome looks like. A few sentences are enough. No spec required.',
      'gets'    => 'A quick reply with clarifying questions — or an honest answer if I\'m not the right fit.',
    ],
    [
      'num'     => '02',
      'title'   => 'Scope.',
      'timing'  => '2–4 days',
      'body'    => 'I send a plain list of work, a rough timeline, a fixed price, and an explicit list of what\'s out of scope. You say yes, push back, or ask questions.',
      'gets'    => 'A written scope document. No retainers, no hourly billing, no surprise invoices.',
    ],
    [
      'num'     => '03',
      'title'   => 'Build.',
      'timing'  => '1–3 weeks',
      'body'    => 'I build in stages and share previews on real pages — not mockups. You give feedback on something you can actually click. Changes happen before launch.',
      'gets'    => 'Staged previews, version-controlled code, and a browser you can share with your team.',
    ],
    [
      'num'     => '04',
      'title'   => 'Yours.',
      'timing'  => 'Handoff day',
      'body'    => 'You get everything: the domain, the hosting account, the database, the code. I write a plain-language guide for the admin and stay reachable for questions.',
      'gets'    => 'Full ownership transfer. No ongoing fees. No lock-in.',
    ],
  ];

  $faqs = [
    ['Do you work with agencies on client projects?', 'Yes. I\'ve worked as a sub on agency projects — you keep the client relationship, I stay in the background. Rate is project-based and agreed before anything starts. I can sign an NDA if needed.'],
    ['What does "you own it" actually mean?', 'The domain is registered in your name. The hosting account is yours. The database and all the files belong to you. After handoff I have no access unless you invite me back. You can take everything to another developer and they\'ll have what they need.'],
    ['How do you price projects?', 'Fixed price, not hourly. After the scope conversation I send a number that covers the work as described. If the scope changes, we talk about it before I build more. No surprise invoices.'],
    ['How long does a WordPress site take?', 'A simple site with a few pages and a contact form: two to three weeks. Something with custom fields, a booking system, or filtering: four to six weeks. I give a realistic estimate during scoping, not an optimistic one.'],
    ['Can I edit the site myself after you hand it off?', 'Yes — that\'s the point. I build edit flows so the owner can change content without touching code. Before launch I document anything that isn\'t obvious in plain language.'],
    ['Do you do design, or just development?', 'Development. I can work from your design, a reference site, or a clear description of what you want. For original visual design work I\'ll refer you to someone who does it well rather than guessing.'],
    ['What kinds of projects are not a good fit?', 'Ongoing social media or ad management. Large enterprise e-commerce from scratch. Sites that need a finished design before I can start and no designer is involved yet. If I\'m not the right fit I\'ll say so and try to point you somewhere useful.'],
    ['Do you take on Power Platform work?', 'Sometimes. When a team already lives in Microsoft 365 and Power Apps or Power Automate is the right tool, I\'ll take it on. It\'s not my main focus and I\'ll say so if WordPress or another stack would serve you better.'],
  ];
@endphp

@section('content')

{{-- HERO --}}
@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('svc_kicker', __('Services', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('svc_h1', __('WordPress development services.', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('svc_lede', __('Custom WordPress sites, plugins, and web apps — built so the owner can run them. Based in Gettysburg, PA. Available for full-time, freelance, and agency overflow work.', 'sage')) }}
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
    <p class="eyebrow">What I do</p>
    <h2 id="svc-ways-heading" class="display-title is-section">
      {{ \App\field('svc_ways_h2', __('Four ways I can help.', 'sage')) }}
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
      Not sure what you need? <a href="{{ home_url('/contact/') }}">Write a note</a> describing the problem and I\'ll help you figure out the right shape.
    </p>
  </div>
</section>

{{-- PROCESS --}}
<section class="pf-section pf-section--alt" aria-labelledby="svc-process-heading">
  <div class="container wide">
    <p class="eyebrow">Process</p>
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

{{-- PRICING TRANSPARENCY --}}
<section class="pf-section" aria-labelledby="svc-pricing-heading">
  <div class="container measure">
    <p class="eyebrow">Pricing</p>
    <h2 id="svc-pricing-heading" class="display-title is-section">
      {{ \App\field('svc_pricing_h2', __('How pricing works.', 'sage')) }}
    </h2>
    <p>{{ \App\field('svc_pricing_p1', __('Every project is a fixed price — not hourly. After we talk through what you need, I send a written scope and a number. That number doesn\'t change unless the scope does, and if it changes we talk before I build more.', 'sage')) }}</p>
    <p>{{ \App\field('svc_pricing_p2', __('I don\'t do retainers or monthly maintenance contracts unless you specifically want one. Most clients don\'t need ongoing developer hours — they need a site that works and a clear path to get help if something breaks.', 'sage')) }}</p>
    <p>{{ \App\field('svc_pricing_p3', __('If budget is a constraint, say so early. A smaller, well-scoped project delivered on time is better than a large project that drags. I\'d rather build something useful in your range than quote something you can\'t move forward on.', 'sage')) }}</p>
    <a class="btn" href="{{ home_url('/contact/') }}" style="margin-top:.5rem">
      {!! \App\mh_svg_icon('mail', 16) !!} Start with a note
    </a>
  </div>
</section>

{{-- FAQ --}}
<section class="pf-section pf-section--alt" aria-labelledby="svc-faq-heading">
  <div class="container wide svc-faq-layout">
    <div>
      <p class="eyebrow">Questions</p>
      <h2 id="svc-faq-heading" class="display-title is-section">
        {{ \App\field('svc_faq_h2', __('Frequently asked.', 'sage')) }}
      </h2>
      <p class="sec-intro">Real questions from real conversations. If yours isn\'t here, <a href="{{ home_url('/contact/') }}">just ask</a>.</p>
    </div>
    <div class="faq-list">
      @foreach ($faqs as $i => [$q, $a])
        <details @if($i === 0) open @endif>
          <summary>{{ $q }}</summary>
          <p>{{ $a }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>

{{-- CTA --}}
<section class="cta-band" aria-labelledby="svc-cta-heading">
  <div class="container wide cta-band-inner">
    <div>
      <p class="eyebrow eyebrow--on-dark">Get started</p>
      <h2 id="svc-cta-heading" class="display-title is-section">
        {{ \App\field('svc_fair_h2', __('Ready to talk?', 'sage')) }}
      </h2>
      <p>Tell me what you\'re building or fixing. A paragraph is enough to start. I reply within a day.</p>
    </div>
    <div style="display:flex;flex-direction:column;gap:.75rem;align-items:flex-end;flex-shrink:0">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!} Write a note
      </a>
      <a class="about-text-link" href="{{ home_url('/projects/') }}" style="color:#9ca3af">
        See example sites →
      </a>
    </div>
  </div>
</section>

@endsection
