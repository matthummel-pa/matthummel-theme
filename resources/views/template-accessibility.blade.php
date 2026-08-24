{{--
  Template Name: Accessibility
--}}
@extends('layouts.app')

@php
  $lastReview = '2026-08-24';
  $contactUrl  = home_url('/contact/');
  $siteUrl     = home_url('/');
@endphp

@section('content')

@component('partials.page-hero')
  <p class="eyebrow">{{ __('Accessibility', 'sage') }}</p>
  <h1 class="display-title is-hero">Accessibility statement.</h1>
  <p class="lead">This site is built to be usable by everyone. Here is what that means in practice and how to reach me if something gets in the way.</p>
@endcomponent

{{-- ── COMMITMENT ────────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="a11y-commitment-heading">
  <div class="container measure">
    <h2 id="a11y-commitment-heading">Our commitment</h2>
    <p>matthummel.com is committed to providing a website that is accessible to the widest possible audience, regardless of technology or ability. I aim to conform to <strong>WCAG 2.1 Level AA</strong> and the requirements of <strong>Section 508</strong> of the Rehabilitation Act of 1973.</p>
    <p>Accessibility is built into the development process here — not retrofitted after the fact. Every template is written with semantic HTML, keyboard navigation, and screen reader compatibility in mind from the start.</p>
  </div>
</section>

{{-- ── STANDARDS ─────────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="a11y-standards-heading">
  <div class="container wide">
    <h2 id="a11y-standards-heading">Standards and guidelines</h2>
    <div class="a11y-standards-grid">

      <div class="a11y-standard-card">
        <div class="a11y-standard-card__badge">WCAG 2.1</div>
        <h3 class="a11y-standard-card__title">Web Content Accessibility Guidelines</h3>
        <p class="a11y-standard-card__body">The W3C's international standard for web accessibility. This site targets <strong>Level AA</strong> conformance — the benchmark required by most government accessibility laws worldwide.</p>
        <ul class="a11y-standard-card__list">
          <li><strong>Perceivable</strong> — content is available to all senses</li>
          <li><strong>Operable</strong> — all functions work via keyboard</li>
          <li><strong>Understandable</strong> — content is clear and predictable</li>
          <li><strong>Robust</strong> — compatible with assistive technology</li>
        </ul>
      </div>

      <div class="a11y-standard-card">
        <div class="a11y-standard-card__badge">§ 508</div>
        <h3 class="a11y-standard-card__title">Section 508 of the Rehabilitation Act</h3>
        <p class="a11y-standard-card__body">The US federal standard requiring electronic and information technology to be accessible to people with disabilities. This site follows the 2017 refresh, which aligns Section 508 with WCAG 2.0 Level AA.</p>
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
    <h2 id="a11y-features-heading">Accessibility features on this site</h2>
    <p class="sec-intro">These are specific implementation choices made to support users of assistive technology and keyboard navigation.</p>
    <div class="a11y-features-grid">

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>Skip navigation link</h3>
          <p>The first focusable element on every page is a "Skip to main content" link. Keyboard users and screen reader users can bypass the navigation and jump straight to the page content.</p>
        </div>
      </div>

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>Semantic HTML landmarks</h3>
          <p>Every page uses correct HTML landmark elements — <code>&lt;header&gt;</code>, <code>&lt;main&gt;</code>, <code>&lt;nav&gt;</code>, <code>&lt;footer&gt;</code>, <code>&lt;aside&gt;</code>, <code>&lt;article&gt;</code>, and <code>&lt;section&gt;</code> — so screen readers can navigate by landmark.</p>
        </div>
      </div>

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>Keyboard navigation</h3>
          <p>All interactive elements are reachable and operable via keyboard alone. The mobile navigation menu includes a full focus trap. The <kbd>Escape</kbd> key closes overlays and menus.</p>
        </div>
      </div>

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>Visible focus indicators</h3>
          <p>Every interactive element shows a clearly visible focus ring when navigated to with a keyboard. Focus is styled with a 2px accent-coloured outline and does not rely on the browser default alone.</p>
        </div>
      </div>

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>Colour contrast</h3>
          <p>Text and interactive elements meet WCAG AA contrast requirements — at least 4.5:1 for body text and 3:1 for large text and UI components. Colour is never the only means of conveying information.</p>
        </div>
      </div>

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>Image alternative text</h3>
          <p>All meaningful images have descriptive <code>alt</code> attributes. Decorative images use <code>alt=""</code> and <code>aria-hidden="true"</code> so screen readers skip them cleanly.</p>
        </div>
      </div>

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>Form accessibility</h3>
          <p>Every form field has a visible, associated <code>&lt;label&gt;</code>. Required fields are indicated both visually and via <code>aria-required</code>. Error messages are linked to the relevant field with <code>aria-describedby</code> and announced via a live region.</p>
        </div>
      </div>

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>Reduced motion support</h3>
          <p>Animations and transitions respect the <code>prefers-reduced-motion</code> media query. Users who have set their system preference to reduce motion see a simplified, non-animated experience.</p>
        </div>
      </div>

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>Resizable text</h3>
          <p>All text is sized in relative units (<code>rem</code>, <code>em</code>, fluid <code>clamp()</code>). Pages remain usable and readable when text is scaled to 200% using browser zoom.</p>
        </div>
      </div>

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>FAQ structured data</h3>
          <p>FAQ sections use semantic <code>&lt;details&gt;</code> / <code>&lt;summary&gt;</code> elements — natively accessible without ARIA augmentation — and include JSON-LD <code>FAQPage</code> schema for search engine rich results.</p>
        </div>
      </div>

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>Structured headings</h3>
          <p>Each page uses a single <code>&lt;h1&gt;</code> followed by a logical <code>&lt;h2&gt;</code> and <code>&lt;h3&gt;</code> hierarchy. Headings are not skipped, and heading levels are not used for visual styling.</p>
        </div>
      </div>

      <div class="a11y-feature">
        {!! \App\mh_svg_icon('check', 18) !!}
        <div>
          <h3>Current page indicator</h3>
          <p>The navigation highlights the current page with <code>aria-current="page"</code> on the active menu item, giving screen reader users a clear signal of where they are on the site.</p>
        </div>
      </div>

    </div>
  </div>
</section>

{{-- ── KNOWN LIMITATIONS ─────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="a11y-limits-heading">
  <div class="container measure">
    <h2 id="a11y-limits-heading">Known limitations</h2>
    <p>No site is perfectly accessible at all times. The following limitations are known and being worked on:</p>
    <ul class="a11y-limits-list">
      <li>
        <strong>Third-party embeds</strong> — GitHub contribution graphs and some external content rendered via API may not fully conform to WCAG AA. These are outside the direct control of this site's code.
      </li>
      <li>
        <strong>Older blog posts</strong> — Posts imported from the previous site version may contain images without alt text or code blocks that lack language annotations. These are being reviewed on an ongoing basis.
      </li>
      <li>
        <strong>PDF documents</strong> — Any linked PDF files have not been assessed for accessibility. If you need an accessible version of a PDF, please <a href="{{ $contactUrl }}">contact me</a> directly.
      </li>
    </ul>
    <p>If you encounter a barrier not listed here, please report it — the feedback helps make the site better for everyone.</p>
  </div>
</section>

{{-- ── TECHNICAL APPROACH ────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="a11y-tech-heading">
  <div class="container measure">
    <h2 id="a11y-tech-heading">Technical approach</h2>
    <p>This site is built on WordPress with a custom Sage 11 theme (Blade templates, Tailwind v4, Vite). The following testing and development practices are used:</p>
    <ul>
      <li>Manual keyboard navigation testing on all templates before release</li>
      <li>Automated axe and Lighthouse accessibility audits on key pages</li>
      <li>Testing with VoiceOver (macOS/iOS) and NVDA (Windows)</li>
      <li>Colour contrast checked against WCAG 2.1 AA ratios using browser developer tools</li>
      <li>HTML validated against the W3C HTML specification</li>
    </ul>
    <p>Accessibility fixes are treated as bugs — not enhancements — and are addressed promptly after discovery.</p>
  </div>
</section>

{{-- ── CONTACT ────────────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="a11y-contact-heading">
  <div class="container measure">
    <h2 id="a11y-contact-heading">Feedback and contact</h2>
    <p>If you experience any accessibility barrier on this site, or if you need content in an alternative format, please get in touch:</p>
    <ul>
      <li><strong>Contact form:</strong> <a href="{{ $contactUrl }}">matthummel.com/contact/</a></li>
      <li><strong>Response time:</strong> I aim to respond within two business days.</li>
    </ul>
    <p>Your feedback is taken seriously and used to improve the site for all visitors. I welcome reports of any issue, no matter how small.</p>
    <div style="margin-top:1.5rem">
      <a class="btn" href="{{ $contactUrl }}">
        {!! \App\mh_svg_icon('mail', 16) !!} Send accessibility feedback
      </a>
    </div>
  </div>
</section>

{{-- ── STATEMENT DETAILS ─────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="a11y-details-heading">
  <div class="container measure">
    <h2 id="a11y-details-heading">Statement details</h2>
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
        <dd>Partially conforms to WCAG 2.1 Level AA — some content listed under Known Limitations does not fully conform.</dd>
      </div>
      <div>
        <dt>Assessment approach</dt>
        <dd>Self-evaluation with automated testing (Lighthouse, axe) and manual keyboard and screen reader review.</dd>
      </div>
    </dl>
  </div>
</section>

@endsection
