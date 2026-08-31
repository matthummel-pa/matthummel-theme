{{--
  Template Name: Accessibility
--}}
@extends('layouts.app')

@php
  $lastReview = '2026-08-24';
  $contactUrl  = home_url('/contact/');
  $siteUrl     = home_url('/');

  $features = [
    ['Skip navigation link',       'The first focusable element on every page is a "Skip to main content" link. Keyboard users and screen reader users can bypass the navigation and jump straight to the page content.'],
    ['Semantic HTML landmarks',    'Every page uses correct landmark elements — <code>&lt;header&gt;</code>, <code>&lt;main&gt;</code>, <code>&lt;nav&gt;</code>, <code>&lt;footer&gt;</code>, <code>&lt;aside&gt;</code>, and <code>&lt;section&gt;</code> — so screen readers can navigate by landmark.'],
    ['Keyboard navigation',        'All interactive elements are reachable via keyboard alone. The mobile menu includes a focus trap. The <kbd>Escape</kbd> key closes overlays and menus.'],
    ['Visible focus indicators',   'Every interactive element shows a 2px accent-coloured focus ring when reached by keyboard. Focus indicators are not suppressed for mouse users via <code>:focus-visible</code>.'],
    ['Colour contrast',            'Text and UI components meet WCAG AA — at least 4.5:1 for body text and 3:1 for large text. Colour is never the only means of conveying information.'],
    ['Image alternative text',     'Meaningful images carry descriptive <code>alt</code> attributes. Decorative images use <code>alt=""</code> and <code>aria-hidden="true"</code> so screen readers skip them.'],
    ['Accessible forms',           'Every field has a visible <code>&lt;label&gt;</code>. Required fields use <code>aria-required</code>. Error messages are linked via <code>aria-describedby</code> and announced through a live region.'],
    ['Reduced motion support',     'Animations respect <code>prefers-reduced-motion</code>. Users with that system preference enabled see a static, non-animated experience throughout the site.'],
    ['Resizable text',             'All text is set in relative units (<code>rem</code>, fluid <code>clamp()</code>). Pages remain readable and functional at 200% browser zoom.'],
    ['FAQ structured data',        'FAQ accordions use native <code>&lt;details&gt;</code> / <code>&lt;summary&gt;</code> elements — accessible without extra ARIA — plus JSON-LD <code>FAQPage</code> schema for search rich results.'],
    ['Logical heading hierarchy',  'Each page has a single <code>&lt;h1&gt;</code> followed by a sequential <code>&lt;h2&gt;</code> → <code>&lt;h3&gt;</code> structure. Heading levels are not skipped or used for visual styling alone.'],
    ['Current page indicator',     'The active navigation item carries <code>aria-current="page"</code>, giving screen reader users a clear signal of their location within the site.'],
  ];

  $limits = [
    [
      'title' => 'Third-party embeds',
      'body'  => 'GitHub contribution graphs and some external API content may not fully conform to WCAG AA. These are outside the direct control of this site\'s code and are flagged for monitoring.',
    ],
    [
      'title' => 'Older blog posts',
      'body'  => 'Posts imported from the previous site may contain images without alt text or code blocks without a language annotation. These are reviewed on an ongoing basis.',
    ],
    [
      'title' => 'PDF documents',
      'body'  => 'Any linked PDF files have not been assessed for accessibility. If you need content in an alternative format, <a href="' . esc_url($contactUrl) . '">contact me</a> and I\'ll sort it.',
    ],
  ];

  $testing = [
    'Manual keyboard navigation testing on all page templates before release',
    'Automated audits using Lighthouse and axe on key pages',
    'Screen reader testing with VoiceOver (macOS / iOS) and NVDA (Windows)',
    'Colour contrast verified against WCAG 2.1 AA ratios in browser developer tools',
    'HTML validated against the W3C specification',
    'Accessibility issues treated as bugs — addressed before new features, not after',
  ];
@endphp

@section('content')

{{-- ── HERO ──────────────────────────────────────────────── --}}
@component('partials.page-hero')
  <p class="eyebrow">Accessibility</p>
  <h1 class="display-title is-hero">Accessibility statement.</h1>
  <p class="lead">This site is built to be usable by everyone. Here is what that means in practice, and how to reach me if something gets in the way.</p>
  <p class="about-hero-links" style="margin-top:1rem">
    <a href="#a11y-features-heading">{!! \App\mh_svg_icon('check', 15) !!} What's implemented</a>
    <a href="#a11y-limits-heading">{!! \App\mh_svg_icon('x-circle', 15) !!} Known limitations</a>
    <a href="{{ $contactUrl }}">{!! \App\mh_svg_icon('mail', 15) !!} Report an issue</a>
  </p>
@endcomponent

{{-- ── MY COMMITMENT ────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="a11y-commitment-heading">
  <div class="container wide">
    <div class="a11y-prose">
      <p class="eyebrow">Commitment</p>
      <h2 id="a11y-commitment-heading">My commitment</h2>
      <p>I'm committed to making matthummel.com accessible to the widest possible audience, regardless of technology or ability. Accessibility is built into the development process here — not added as an afterthought.</p>
      <ul class="a11y-commit-list">
        <li>Target conformance with <strong>WCAG 2.1 Level AA</strong> — the benchmark required by most government accessibility laws worldwide</li>
        <li>Meet the requirements of <strong>Section 508</strong> of the Rehabilitation Act of 1973 (2017 refresh)</li>
        <li>Treat every accessibility report as a bug fix, not a feature request</li>
        <li>Review and update this statement whenever the site changes substantially</li>
      </ul>
    </div>
  </div>
</section>

{{-- ── STANDARDS ─────────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="a11y-standards-heading">
  <div class="container wide">
    <p class="eyebrow">Standards</p>
    <h2 id="a11y-standards-heading">Guidelines followed</h2>
    <p class="sec-intro" style="max-width:52ch;margin-bottom:2rem">Two overlapping standards govern the accessibility of this site.</p>
    <div class="a11y-standards-grid">

      <div class="a11y-standard-card">
        <div class="a11y-standard-card__badge">WCAG 2.1</div>
        <h3 class="a11y-standard-card__title">Web Content Accessibility Guidelines</h3>
        <p class="a11y-standard-card__body">The W3C's international standard for web accessibility. This site targets <strong>Level AA</strong> — the tier required by most national accessibility regulations.</p>
        <ul class="a11y-standard-card__list">
          <li><strong>Perceivable</strong> — content available to all senses</li>
          <li><strong>Operable</strong> — all functions reachable by keyboard</li>
          <li><strong>Understandable</strong> — content is clear and predictable</li>
          <li><strong>Robust</strong> — compatible with assistive technology</li>
        </ul>
      </div>

      <div class="a11y-standard-card">
        <div class="a11y-standard-card__badge">§ 508</div>
        <h3 class="a11y-standard-card__title">Section 508 — Rehabilitation Act</h3>
        <p class="a11y-standard-card__body">The US federal standard for electronic and information technology accessibility. This site follows the 2017 refresh, which aligns §508 with WCAG 2.0 Level AA.</p>
        <ul class="a11y-standard-card__list">
          <li>Functional performance criteria met</li>
          <li>Software and web-based content covered</li>
          <li>Applies to federal agencies and their contractors</li>
        </ul>
      </div>

    </div>
  </div>
</section>

{{-- ── FEATURES ──────────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="a11y-features-heading">
  <div class="container wide">
    <p class="eyebrow">What's implemented</p>
    <h2 id="a11y-features-heading">Accessibility features on this site</h2>
    <p class="sec-intro" style="max-width:52ch;margin-bottom:2.25rem">Specific implementation choices made to support assistive technology and keyboard navigation.</p>
    <div class="a11y-features-grid">
      @foreach ($features as [$title, $body])
        <div class="a11y-feature">
          <div class="a11y-feature__check" aria-hidden="true">
            {!! \App\mh_svg_icon('check', 14) !!}
          </div>
          <div class="a11y-feature__content">
            <h3>{{ $title }}</h3>
            <p>{!! $body !!}</p>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ── KNOWN LIMITATIONS ─────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="a11y-limits-heading">
  <div class="container wide">
    <div class="a11y-prose">
      <p class="eyebrow">Limitations</p>
      <h2 id="a11y-limits-heading">Known limitations</h2>
      <p>No site is perfectly accessible at all times. The following are known and actively monitored:</p>
    </div>
    <div class="a11y-limits-grid">
      @foreach ($limits as $lim)
        <div class="a11y-limit-card">
          <div class="a11y-limit-card__icon">{!! \App\mh_svg_icon('x-circle', 16) !!}</div>
          <div>
            <h3 class="a11y-limit-card__title">{{ $lim['title'] }}</h3>
            <p class="a11y-limit-card__body">{!! $lim['body'] !!}</p>
          </div>
        </div>
      @endforeach
    </div>
    <p class="a11y-prose" style="margin-top:1.5rem;font-size:.9rem;color:var(--color-text-secondary)">
      If you hit a barrier not listed here, <a href="{{ $contactUrl }}">please report it</a> — every report improves the site for everyone.
    </p>
  </div>
</section>

{{-- ── TECHNICAL APPROACH ────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="a11y-tech-heading">
  <div class="container wide">
    <div class="a11y-two-col">
      <div class="a11y-prose">
        <p class="eyebrow">How I test</p>
        <h2 id="a11y-tech-heading">Technical approach</h2>
        <p>This site runs on WordPress with a custom Sage 11 theme. Accessibility is verified through the following practices on every release:</p>
      </div>
      <ul class="a11y-tech-list">
        @foreach ($testing as $item)
          <li>{!! $item !!}</li>
        @endforeach
      </ul>
    </div>
  </div>
</section>

{{-- ── CONTACT ────────────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="a11y-contact-heading">
  <div class="container wide">
    <div class="a11y-contact-layout">
      <div class="a11y-prose">
        <p class="eyebrow">Get in touch</p>
        <h2 id="a11y-contact-heading">Report an issue</h2>
        <p>If you hit an accessibility barrier — or need content in an alternative format — please reach out. I take every report seriously and aim to respond within two business days.</p>
        <a class="btn" href="{{ $contactUrl }}" style="margin-top:1rem;display:inline-flex">
          {!! \App\mh_svg_icon('mail', 16) !!} Send accessibility feedback
        </a>
      </div>
      <div class="a11y-contact-card">
        <p class="a11y-contact-card__label">Contact options</p>
        <ul class="a11y-contact-card__list">
          <li>
            <strong>Contact form</strong>
            <a href="{{ $contactUrl }}">matthummel.com/contact/</a>
          </li>
          <li>
            <strong>Response time</strong>
            Within two business days
          </li>
          <li>
            <strong>Alternative formats</strong>
            Available on request
          </li>
        </ul>
      </div>
    </div>
  </div>
</section>

{{-- ── STATEMENT DETAILS ─────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="a11y-details-heading">
  <div class="container wide">
    <div class="a11y-prose">
      <p class="eyebrow">Statement</p>
      <h2 id="a11y-details-heading">Statement details</h2>
    </div>
    <dl class="a11y-details-list">
      <div>
        <dt>Website</dt>
        <dd><a href="{{ $siteUrl }}">matthummel.com</a></dd>
      </div>
      <div>
        <dt>Statement prepared</dt>
        <dd><time datetime="{{ $lastReview }}">{{ date('F j, Y', strtotime($lastReview)) }}</time></dd>
      </div>
      <div>
        <dt>Last reviewed</dt>
        <dd><time datetime="{{ $lastReview }}">{{ date('F j, Y', strtotime($lastReview)) }}</time></dd>
      </div>
      <div>
        <dt>Conformance status</dt>
        <dd>Partially conforms to WCAG 2.1 Level AA — some content listed under Known Limitations does not yet fully conform.</dd>
      </div>
      <div>
        <dt>Assessment approach</dt>
        <dd>Self-evaluation using Lighthouse, axe, and manual keyboard and screen reader review.</dd>
      </div>
    </dl>
  </div>
</section>

@include('partials.cta-band', [
  'kicker' => __('Accessibility', 'sage'),
  'title' => __('Need help using this site?', 'sage'),
  'text' => __('If something blocks you, write and tell me what you ran into. I take accessibility reports seriously.', 'sage'),
  'label' => __('Write a note', 'sage'),
  'secondary' => '',
  'note' => __('Remote · usually within a day', 'sage'),
])

@endsection
