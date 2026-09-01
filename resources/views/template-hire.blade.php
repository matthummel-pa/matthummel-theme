{{--
  Template Name: Hire
--}}
@extends('layouts.app')

@php
  $gh = \App\Github::fetchUser(\App\mh_github_login());
  $li = \App\LinkedIn::fetchProfile();
  $liUrl = (string) ($li['url'] ?? \App\LinkedIn::profileUrl());
  $shareUrl = \App\LinkedIn::shareUrl(home_url('/hire/'));
  $jobs = \App\mh_code_page_resume();
  $skills = \App\mh_code_page_skills(\App\mh_page_id_by_template('template-code.blade.php') ?: null);
  $roleCount = count($jobs);
  $currentRole = '';
  foreach ($jobs as $job) {
      if (strcasecmp((string) ($job['period'] ?? ''), 'Current') === 0) {
          $currentRole = trim(($job['role'] ?? '').(($job['org'] ?? '') !== '' ? ' · '.$job['org'] : ''));
          break;
      }
  }

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

  $fit = [
    ['Clear scope', 'Written list of work, timeline, and what is out of scope before build starts.'],
    ['Editable handoff', 'WordPress admin the shop can keep using — not a locked page builder.'],
    ['Public proof', 'Repos and commits on GitHub; roles on LinkedIn match the resume below.'],
  ];
@endphp

@section('content')

{{-- ── HERO ────────────────────────────────────────────── --}}
@component('partials.page-hero', ['split' => true, 'asideLabel' => __('Hire snapshot', 'sage')])
  <p class="eyebrow">{{ \App\field('hire_kicker', __('Hire me', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('hire_h1', __('Hire a WordPress developer.', 'sage')) }}
  </h1>
  <p class="lead">{{ \App\field('hire_lede', __('Open for full-time, contract, freelance, and a handful of agency-overflow jobs. Seventeen years of in-house web work; public Sage/WordPress on GitHub since 2025. Remote or on-site near Gettysburg.', 'sage')) }}</p>
  @if (\App\mh_is_hireable($gh) || ! empty($li['open_to_work']))
    <p class="hire-avail">
      @include('partials.avail-mark', ['gh' => $gh])
      {{ \App\mh_availability_label($gh, __('Currently available', 'sage')) }} — reply within a day
    </p>
  @endif
  <div class="page-header-split__actions">
    <a class="btn" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!} Say hello
    </a>
    <a class="btn btn-outline" href="{{ esc_url($liUrl) }}" rel="noopener" target="_blank">
      {!! \App\mh_svg_icon('linkedin', 16) !!} LinkedIn
      <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
    </a>
    <a class="h-text-arrow" href="{{ home_url('/services/') }}">
      Full services detail <span aria-hidden="true">→</span>
    </a>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/hire',
      'icon' => 'briefcase',
      'title' => __('Good fit for', 'sage'),
      'meta' => __('Shops · agencies · developers', 'sage'),
      'status' => (\App\mh_is_hireable($gh) || ! empty($li['open_to_work']))
        ? ['label' => \App\mh_availability_label($gh, __('Open', 'sage')), 'gh' => $gh]
        : null,
      'stats' => [
        ['value' => number_format_i18n($roleCount), 'label' => __('Roles on resume', 'sage')],
        ['value' => number_format_i18n(count($skills)), 'label' => __('Skills listed', 'sage')],
        ['value' => __('Remote', 'sage'), 'label' => __('On-site welcome', 'sage')],
        ['value' => __('Full stack', 'sage'), 'label' => __('WordPress focus', 'sage')],
      ],
      'link' => [
        'label' => __('View LinkedIn', 'sage'),
        'href' => $liUrl,
        'external' => true,
      ],
    ])
  @endslot
@endcomponent

{{-- ── LINKEDIN PROFILE ────────────────────────────────── --}}
<section class="pf-section pf-section--alt" id="linkedin" aria-labelledby="hire-li-heading">
  <div class="container wide">
    <div class="code-gh__head">
      <div>
        <p class="eyebrow">{{ __('Professional network', 'sage') }}</p>
        <h2 id="hire-li-heading" class="display-title is-section">
          {{ \App\field('hire_li_h2', __('LinkedIn profile.', 'sage')) }}
        </h2>
        <p class="sec-intro">
          {{ \App\field('hire_li_intro', __('Roles below match my LinkedIn. Connect there for a quieter inbox, or write here if you already know what you need.', 'sage')) }}
        </p>
      </div>
    </div>

    <div class="hire-li-panel">
      <div class="hire-li-panel__mesh" aria-hidden="true"></div>
      <div class="hire-li-panel__main">
        <div class="hire-li-panel__who">
          @if (! empty($li['picture']))
            <img class="hire-li-panel__avatar" src="{{ esc_url($li['picture']) }}" width="88" height="88" alt="{{ esc_attr(($li['name'] ?? 'Matt Hummel').' profile photo') }}" loading="lazy" decoding="async">
          @endif
          <div class="hire-li-panel__copy">
            <p class="hire-li-panel__name">
              {!! \App\mh_svg_icon('linkedin', 18) !!}
              {{ $li['name'] ?? 'Matt Hummel' }}
            </p>
            @if (! empty($li['headline']))
              <p class="hire-li-panel__headline">{{ $li['headline'] }}</p>
            @endif
            <p class="hire-li-panel__meta">
              @if (! empty($li['location']))
                <span>{!! \App\mh_svg_icon('map', 13) !!} {{ $li['location'] }}</span>
              @endif
              @if (! empty($li['open_to_work']))
                <span class="hire-li-open">
                  <span class="h-badge__dot" aria-hidden="true"></span>
                  {{ __('Open to work', 'sage') }}
                </span>
              @endif
              @if (($li['source'] ?? '') === 'api')
                <span class="hire-li-live" aria-label="{{ __('Live data from LinkedIn API', 'sage') }}">
                  <span class="h-badge__dot" aria-hidden="true"></span>
                  {{ __('Live from API', 'sage') }}
                </span>
              @endif
            </p>
            @if (! empty($li['about']))
              <p class="hire-li-panel__about">{{ $li['about'] }}</p>
            @endif
            <p class="hire-li-panel__actions">
              <a class="btn" href="{{ esc_url($liUrl) }}" rel="noopener" target="_blank">
                {!! \App\mh_svg_icon('linkedin', 15) !!} {{ __('View on LinkedIn', 'sage') }}
                <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
              </a>
              <a class="btn btn-outline" href="{{ esc_url($shareUrl) }}" rel="noopener" target="_blank">
                {!! \App\mh_svg_icon('globe', 15) !!} {{ __('Share this page', 'sage') }}
                <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
              </a>
              <a class="about-text-link" href="{{ home_url('/contact/') }}">
                {!! \App\mh_svg_icon('mail', 14) !!} {{ __('Prefer email →', 'sage') }}
              </a>
            </p>
          </div>
        </div>
        <dl class="hire-li-stats">
          @if ($currentRole !== '')
            <div class="hire-li-stat">
              <dt>{{ __('Current', 'sage') }}</dt>
              <dd>{{ $currentRole }}</dd>
            </div>
          @endif
          <div class="hire-li-stat">
            <dt>{{ number_format_i18n($roleCount) }}</dt>
            <dd>{{ __('Roles on resume', 'sage') }}</dd>
          </div>
          @if (! empty($gh['public_repos']))
            <div class="hire-li-stat">
              <dt>{{ number_format_i18n((int) $gh['public_repos']) }}</dt>
              <dd>{{ __('Public GitHub repos', 'sage') }}</dd>
            </div>
          @endif
        </dl>
      </div>
    </div>

    <div class="hire-fit" aria-label="{{ __('Why hire', 'sage') }}">
      @foreach ($fit as [$title, $text])
        <article class="hire-fit__card">
          <h3>{{ $title }}</h3>
          <p>{{ $text }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- ── RESUME ──────────────────────────────────────────── --}}
@include('partials.resume-timeline', [
  'jobs' => $jobs,
  'linkedin' => $liUrl,
  'headingId' => 'hire-resume-heading',
  'h2' => \App\field('hire_cv_h2', __('Resume.', 'sage')),
  'intro' => \App\field('hire_cv_intro', __('Working with shops and agencies anywhere. Open to full-time, contract, and agency overflow work.', 'sage')),
  'eyebrow' => __('Experience', 'sage'),
  'extraLinks' => [
    ['href' => home_url('/about/'), 'label' => __('Full background →', 'sage')],
    ['href' => home_url('/code/'), 'label' => __('Public code →', 'sage')],
  ],
])

{{-- ── SKILLS ──────────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" id="skills" aria-labelledby="hire-skills-heading">
  <div class="container wide">
    <p class="eyebrow">{{ __('Stack', 'sage') }}</p>
    <h2 id="hire-skills-heading" class="display-title is-section">
      {{ \App\field('hire_sk_h2', __('Skills I bring.', 'sage')) }}
    </h2>
    <p class="sec-intro">
      {{ \App\field('hire_sk_intro', __('Stack I use on shipped WordPress and web work — same tools you will see on GitHub.', 'sage')) }}
    </p>
    <ul class="skill-row" style="margin-top:1.5rem">
      @foreach ($skills as $skill)
        <li>{!! \App\mh_skill_chip($skill) !!}</li>
      @endforeach
    </ul>
  </div>
</section>

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
        <p class="hire-need-cta__note">
          Or <a href="{{ esc_url($liUrl) }}" rel="noopener" target="_blank">message on LinkedIn</a>
          · <a href="{{ home_url('/services/') }}">Read services →</a>
        </p>
      </div>
    </div>
  </div>
</section>

{{-- ── FOUR STEPS ─────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" id="process" aria-labelledby="hire-process-heading">
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
<section class="pf-section" id="handoff" aria-labelledby="hire-get-heading">
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
<section class="cta-band" id="contact-cta" aria-labelledby="hire-cta-heading" data-reveal>
  <div class="container wide cta-band-inner">
    <div class="cta-band__copy">
      <p class="eyebrow eyebrow--on-dark">{{ __('Let’s go', 'sage') }}</p>
      <h2 id="hire-cta-heading" class="display-title is-section">{{ __('Ready to start?', 'sage') }}</h2>
      <p>{{ __('Write a short note about what you’re building. I’ll reply within a day. LinkedIn works too if you prefer.', 'sage') }}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!} {{ __('Say hello', 'sage') }}
      </a>
      <a class="btn btn-ghost" href="{{ esc_url($liUrl) }}" rel="noopener" target="_blank">
        {!! \App\mh_svg_icon('linkedin', 14) !!} LinkedIn
        <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
      </a>
      <p class="cta-band__note">{{ __('Remote · usually within a day', 'sage') }}</p>
    </div>
  </div>
</section>

@endsection
