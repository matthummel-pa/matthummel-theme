{{--
  Template Name: Services
--}}
@extends('layouts.app')

@php
  $gh       = \App\Github::fetchUser(\App\mh_github_login());
  $ghUrl    = 'https://github.com/'.esc_attr(\App\mh_github_login());
  $featured = array_slice(\App\mh_work_page_items(), 0, 3);

  $services = [
    [
      'icon'  => 'wordpress',
      'num'   => '01',
      'title' => 'WordPress sites',
      'short' => 'New builds, redesigns, and landing pages.',
      'body'  => 'A custom Sage / Blade theme with wp-admin edit fields for every content area that matters — no developer needed for day-to-day updates. Mobile-first, fast, accessible, and 100% yours.',
      'gets'  => ['Custom Sage / Blade theme', 'Admin fields you edit yourself', 'Mobile-first, accessible markup', 'Plain-language handoff guide'],
      'tech'  => ['WordPress', 'Sage', 'PHP', 'Tailwind'],
    ],
    [
      'icon'  => 'plugins',
      'num'   => '02',
      'title' => 'WordPress plugins',
      'short' => 'Custom functionality with no bloat.',
      'body'  => 'Small, focused plugins that do one job well. Built with PHP, documented so any developer can pick them up, and sized to solve the actual problem — not a general-purpose toolkit.',
      'gets'  => ['Single-purpose, readable code', 'PHPDoc on all public functions', 'Clean uninstall — no leftover data', 'Standard WordPress hooks throughout'],
      'tech'  => ['PHP', 'WordPress', 'REST API'],
    ],
    [
      'icon'  => 'code',
      'num'   => '03',
      'title' => 'Other web apps',
      'short' => 'When WordPress isn\'t the right fit.',
      'body'  => 'React front-ends, REST APIs, and data-driven apps. I also take Power Platform work — Power Apps and Power Automate — when a team already lives in Microsoft 365 and it\'s the right tool.',
      'gets'  => ['React or vanilla JS front-ends', 'REST APIs and data integrations', 'Power Apps / Power Automate', 'GitHub repo with full commit history'],
      'tech'  => ['React', 'JavaScript', 'Power Apps', 'REST'],
    ],
    [
      'icon'  => 'users',
      'num'   => '04',
      'title' => 'Agency and overflow work',
      'short' => 'Sub-contracting on your projects.',
      'body'  => 'You keep the client relationship and the billing. I build the WordPress site, plugin, or app. Scope is agreed between us before anything starts. I can sign an NDA.',
      'gets'  => ['You keep the client relationship', 'Written scope agreed upfront', 'Clean handoff to your team', 'NDA available if needed'],
      'tech'  => ['WordPress', 'PHP', 'Sage', 'React'],
    ],
  ];

  $processSteps = [
    [
      'num'    => '01',
      'title'  => 'Write.',
      'timing' => '1–2 days',
      'body'   => 'Tell me who the site is for, what needs fixing, and what a good outcome looks like. A few sentences are enough. No spec, no wireframe required.',
      'gets'   => 'A quick reply with clarifying questions — or an honest note if I\'m not the right fit.',
    ],
    [
      'num'    => '02',
      'title'  => 'Scope.',
      'timing' => '2–4 days',
      'body'   => 'I send a plain list of work, a rough timeline, and an explicit list of what\'s out of scope. You approve, push back, or ask questions.',
      'gets'   => 'A written scope document. No ongoing contracts unless you want one.',
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

  $audiences = [
    [
      'icon'   => 'home',
      'title'  => 'Shops and local businesses',
      'body'   => 'You need a site that you own outright and can update yourself — no developer required for everyday changes. Based in Gettysburg or anywhere else.',
      'fits'   => [
        'You want real ownership of your domain, hosting, and code',
        'You need to edit pages, menus, and images without technical help',
        'You\'re a tour, inn, shop, restaurant, or service business',
        'You want a written scope agreed before anything starts',
      ],
      'cta'    => 'See example sites',
      'href'   => '/projects/',
      'accent' => 'blue',
    ],
    [
      'icon'   => 'users',
      'title'  => 'Agencies with overflow',
      'body'   => 'You have a client project and need a WordPress developer who stays in the background. You keep the relationship, the billing, and the credit.',
      'fits'   => [
        'You need a developer who won\'t contact your client directly',
        'Scope and rate are agreed upfront — no surprises mid-project',
        'You want clean, handoff-ready code your team can maintain',
        'NDA is available if your client requires it',
      ],
      'cta'    => 'Start a conversation',
      'href'   => '/contact/',
      'accent' => 'navy',
    ],
    [
      'icon'   => 'code',
      'title'  => 'Developers and teams',
      'body'   => 'You need a focused plugin, a REST integration, or a second pair of hands on a specific WordPress problem. Code comes documented and clean.',
      'fits'   => [
        'You need a plugin that does one thing and does it well',
        'You want PHPDoc on public functions and readable commit history',
        'You\'re building a React front-end or REST API integration',
        'Power Apps or Power Automate work in a Microsoft 365 environment',
      ],
      'cta'    => 'See the code',
      'href'   => '/code/',
      'accent' => 'green',
    ],
  ];

  $notFits = [
    'Ongoing social media management or paid ad campaigns',
    'Enterprise e-commerce built from scratch with no existing design',
    'Projects with no clear decision-maker or point of contact',
    'Scope that needs to stay intentionally vague — I work from written scope',
  ];

  $commitments = [
    ['icon' => 'check', 'label' => 'Written scope before work starts'],
    ['icon' => 'check', 'label' => 'You own everything at handoff'],
    ['icon' => 'check', 'label' => 'No lock-in or ongoing contracts'],
    ['icon' => 'check', 'label' => 'Gettysburg & remote'],
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
      'q' => 'What does a finished WordPress site from you look like?',
      'a' => 'The example sites on the Work page are the clearest answer — they\'re working concept sites built for Gettysburg businesses. In practice: a custom theme, pages the owner can edit in wp-admin, fast load times, and mobile-first markup. No page-builder clutter.',
    ],
    [
      'q' => 'Do you work with businesses outside Gettysburg?',
      'a' => 'Yes. Most of my work is remote. I\'m based in Gettysburg but I\'ve worked with agencies and businesses across the US. The process works entirely over email, video calls, and shared previews.',
    ],
    [
      'q' => 'What\'s included in the project handoff?',
      'a' => 'You get the domain (if I registered it for you), the hosting account transferred to your name, a full database export, the code repository, and a plain-language admin guide covering every editable area of the site. I also stay reachable for questions after launch.',
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

{{-- FAQ JSON-LD --}}
@php
  $faqSchema  = array_map(fn($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $faqs);
  $faqJsonLd  = json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqSchema], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
@endphp
<script type="application/ld+json">{!! $faqJsonLd !!}</script>

{{-- ── HERO ─────────────────────────────────────────── --}}
@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('svc_kicker', __('WordPress developer · Gettysburg, PA', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('svc_h1', __('WordPress development for shops, agencies, and developers.', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('svc_lede', __('Technical execution for your next WordPress project. I work with local businesses, agencies that need a sub-contractor, and developers who need a focused plugin or integration. Clear scope before anything starts. You own everything at handoff.', 'sage')) }}
  </p>
  <div class="svc-hero-actions">
    <a class="btn" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!} Say hello
    </a>
    <a class="h-text-arrow" href="{{ home_url('/projects/') }}">
      See example sites <span aria-hidden="true">→</span>
    </a>
  </div>
@endcomponent

{{-- ── COMMITMENT STRIP ────────────────────────────── --}}
<div class="svc-strip" aria-label="Key commitments">
  <div class="container wide svc-strip__inner">
    @foreach ($commitments as $c)
      <div class="svc-strip__item">
        {!! \App\mh_svg_icon($c['icon'], 15) !!}
        <span>{{ $c['label'] }}</span>
      </div>
    @endforeach
  </div>
</div>

{{-- ── WHO THIS IS FOR ─────────────────────────────── --}}
<section class="pf-section" aria-labelledby="svc-who-heading" id="fit">
  <div class="container wide">
    <p class="eyebrow">Who I work with</p>
    <h2 id="svc-who-heading" class="display-title is-section">Is this the right fit?</h2>
    <p class="sec-intro" style="max-width:52ch">I work with shops, agencies, and developers — in Gettysburg and remotely. Pick the one that sounds most like you.</p>

    <div class="svc-audience-grid">
      @foreach ($audiences as $aud)
        <div class="svc-audience-card">
          <div class="svc-audience-card__icon">{!! \App\mh_svg_icon($aud['icon'], 22) !!}</div>
          <h3 class="svc-audience-card__title">{{ $aud['title'] }}</h3>
          <p class="svc-audience-card__body">{{ $aud['body'] }}</p>
          <p class="svc-audience-card__fits-label">Good fit if:</p>
          <ul class="svc-audience-card__fits">
            @foreach ($aud['fits'] as $fit)
              <li>{{ $fit }}</li>
            @endforeach
          </ul>
          <a class="svc-audience-card__link" href="{{ home_url($aud['href']) }}">
            {{ $aud['cta'] }} →
          </a>
        </div>
      @endforeach
    </div>

    {{-- Not a good fit callout --}}
    <div class="svc-not-fit">
      <p class="svc-not-fit__heading">
        {!! \App\mh_svg_icon('x-circle', 16) !!}
        Not a good fit
      </p>
      <ul class="svc-not-fit__list">
        @foreach ($notFits as $nf)
          <li>{{ $nf }}</li>
        @endforeach
      </ul>
      <p class="svc-not-fit__close">If none of the above apply and you're unsure, <a href="{{ home_url('/contact/') }}">write anyway</a> — I'll be straight with you.</p>
    </div>

  </div>
</section>

{{-- ── SERVICES ─────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="svc-ways-heading">
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
          <div class="svc-v2-card__tech" aria-label="Technologies">
            @foreach ($svc['tech'] as $t)
              <span class="svc-tech-chip">{{ $t }}</span>
            @endforeach
          </div>
        </article>
      @endforeach
    </div>

    {{-- Mid-page CTA --}}
    <div class="svc-mid-cta">
      <p class="svc-mid-cta__copy">Have a project in mind? I usually reply within a day.</p>
      <a class="h-text-arrow" href="{{ home_url('/contact/') }}">
        Get in touch <span aria-hidden="true">→</span>
      </a>
    </div>
  </div>
</section>

{{-- ── PROCESS ─────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="svc-process-heading">
  <div class="container wide">
    <p class="eyebrow">How it works</p>
    <h2 id="svc-process-heading" class="display-title is-section">
      {{ \App\field('svc_process_h2', __('How a project goes.', 'sage')) }}
    </h2>
    <p class="sec-intro" style="margin-bottom:2.25rem">Four steps. Written scope. You own everything at the end.</p>
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

{{-- ── WORK TEASER ─────────────────────────────────── --}}
@if (! empty($featured))
<section class="pf-section pf-section--alt" aria-labelledby="svc-work-heading">
  <div class="container wide">
    <p class="eyebrow">Example work</p>
    <h2 id="svc-work-heading" class="display-title is-section">A few recent concepts.</h2>
    <p class="sec-intro" style="margin-bottom:2rem">These are concept sites from <a href="https://ridgesandvalleys.com" rel="noopener" target="_blank">Ridges &amp; Valleys</a> — working demonstrations of what a WordPress site can look like for local Gettysburg businesses.</p>
    <div class="svc-work-grid">
      @foreach ($featured as $p)
        @php
          $href = ! empty($p['concept']) ? esc_url($p['concept']) : home_url('/projects/#'.$p['slug']);
          $ext  = ! empty($p['concept']);
        @endphp
        <a class="svc-work-card" href="{{ $href }}" {{ $ext ? 'rel="noopener" target="_blank"' : '' }}>
          @if (! empty($p['image']))
            <div class="svc-work-card__img">
              <img src="{{ esc_url($p['image']) }}"
                   alt="{{ esc_attr($p['title']) }} — WordPress site concept"
                   width="480" height="270" loading="lazy" decoding="async">
            </div>
          @else
            <div class="svc-work-card__img svc-work-card__img--placeholder">
              {!! \App\mh_svg_icon('wordpress', 28) !!}
            </div>
          @endif
          <div class="svc-work-card__body">
            <span class="svc-work-card__cat">{{ $p['cat'] }}</span>
            <h3 class="svc-work-card__title">{{ $p['title'] }}</h3>
            <p class="svc-work-card__place">{!! \App\mh_svg_icon('map', 12) !!} {{ $p['place'] }}</p>
          </div>
        </a>
      @endforeach
    </div>
    <p style="margin-top:1.75rem;text-align:center">
      <a class="h-text-arrow" href="{{ home_url('/projects/') }}">
        Browse all {{ count(\App\mh_work_page_items()) }} concepts →
      </a>
    </p>
  </div>
</section>
@endif

{{-- ── FAQ ─────────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="svc-faq-heading" id="faq">
  <div class="container wide svc-faq-layout">

    {{-- Left: heading + context + sticky contact prompt --}}
    <div class="svc-faq-aside">
      <p class="eyebrow">Questions</p>
      <h2 id="svc-faq-heading" class="display-title is-section">
        {{ \App\field('svc_faq_h2', __('Frequently asked.', 'sage')) }}
      </h2>
      <p class="svc-faq-aside__intro">Common questions about WordPress development, project scope, ownership, and working together — in Gettysburg and remotely.</p>
      <ul class="svc-faq-aside__topics">
        <li>Gettysburg &amp; remote work</li>
        <li>Agency sub-contracting</li>
        <li>Project timelines</li>
        <li>Full ownership at handoff</li>
        <li>Editing after launch</li>
      </ul>
      <div class="svc-faq-aside__cta">
        <p>Question not here?</p>
        <a class="btn btn--sm" href="{{ home_url('/contact/') }}">
          {!! \App\mh_svg_icon('mail', 14) !!} Ask me directly
        </a>
      </div>
    </div>

    {{-- Right: accordion list --}}
    <div>
      <div class="faq-list">
        @foreach ($faqs as $i => $faq)
          <details {{ $i === 0 ? 'open' : '' }}>
            <summary>{{ $faq['q'] }}</summary>
            <p>{{ $faq['a'] }}</p>
          </details>
        @endforeach
      </div>

      {{-- End-of-list prompt --}}
      <div class="faq-end-cta">
        <p class="faq-end-cta__copy">Still have a question? I usually reply within a day.</p>
        <a href="{{ home_url('/contact/') }}" class="h-text-arrow">
          Write a note <span aria-hidden="true">→</span>
        </a>
      </div>
    </div>

  </div>
</section>

{{-- ── CTA ──────────────────────────────────────────── --}}
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
