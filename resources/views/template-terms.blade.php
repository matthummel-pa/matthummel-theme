{{--
  Template Name: Terms of Use
--}}
@extends('layouts.app')

@php
  $updated    = '2026-11-02';
  $contactUrl = home_url('/contact/');
  $privacyUrl = \App\mh_privacy_policy_url();
  $siteUrl    = 'https://matthummel.com';
@endphp

@section('content')

@component('partials.page-hero')
  <p class="eyebrow">Legal</p>
  <h1 class="display-title is-hero">Terms of use</h1>
  <p class="lead">The terms that apply when you use this site or copy its code and content. Plain language.</p>
@endcomponent

@include('partials.page-nav', [
  'pills' => [
    ['terms-overview-heading', __('Overview', 'sage')],
    ['terms-ip-heading', __('IP', 'sage')],
    ['terms-store-heading', __('Theme purchases', 'sage')],
    ['terms-use-heading', __('Acceptable use', 'sage')],
    ['terms-disclaimer-heading', __('No warranty', 'sage')],
    ['terms-liability-heading', __('Liability', 'sage')],
    ['terms-law-heading', __('Governing law', 'sage')],
    ['terms-contact-heading', __('Questions', 'sage')],
  ],
])

{{-- ── OVERVIEW ─────────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="terms-overview-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Overview</p>
      <h2 id="terms-overview-heading">The short version</h2>
      <ul class="legal-bullets">
        <li>Code snippets and examples on this site are free to copy and use in your own projects.</li>
        <li>Written content (articles, posts, page copy) is © Matt Hummel — please link rather than republish in full.</li>
        <li>Example projects shown on the <a href="{{ home_url('/projects/') }}">Work page</a> are demonstrations only — they represent fictional businesses.</li>
        <li>Themes I sell are covered by <a href="#terms-store-heading">Theme purchases</a> below, the <a href="{{ home_url('/license/') }}">license</a> and the <a href="{{ home_url('/refunds/') }}">refund policy</a>, not by the free-to-copy code line above.</li>
        <li>This site is provided as-is with no warranty.</li>
        <li>Use of this site for scraping, spam, or abuse is not permitted.</li>
      </ul>
      <p class="legal-meta">Last updated: <time datetime="{{ $updated }}">{{ date('F j, Y', strtotime($updated)) }}</time></p>
    </div>
  </div>
</section>

{{-- ── INTELLECTUAL PROPERTY ────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="terms-ip-heading">
  <div class="container wide">
    <p class="eyebrow">Content</p>
    <h2 id="terms-ip-heading">Intellectual property</h2>
    <div class="legal-data-grid">

      <div class="legal-data-card">
        <h3 class="legal-data-card__title">Code — free to copy</h3>
        <p class="legal-data-card__intro">Code snippets, GitHub repositories, and examples published on this site are available under the <strong>MIT Licence</strong> unless a repository's own license says otherwise.</p>
        <ul class="legal-data-card__list">
          <li>Copy, modify, and use in personal or commercial projects</li>
          <li>No attribution required, though appreciated</li>
          <li>No warranty — use at your own risk</li>
        </ul>
      </div>

      <div class="legal-data-card">
        <h3 class="legal-data-card__title">Written content — all rights reserved</h3>
        <p class="legal-data-card__intro">Blog posts, page copy, and other written content are © Matt Hummel. Short excerpts (under 100 words) with a link back are fine. Please do not republish full articles without permission.</p>
        <ul class="legal-data-card__list">
          <li>Quote with attribution and a link — always fine</li>
          <li>Full republication — ask first</li>
          <li>AI training use — not permitted without written agreement</li>
        </ul>
      </div>

      <div class="legal-data-card">
        <h3 class="legal-data-card__title">Example projects — demonstrations</h3>
        <p class="legal-data-card__intro">The work shown on the <a href="{{ home_url('/projects/') }}">Work page</a> represents example or demonstration sites. The businesses depicted are fictional or are used for illustrative purposes only.</p>
        <ul class="legal-data-card__list">
          <li>Screenshots may be shared with a link to this site</li>
          <li>Not to be presented as real client work</li>
          <li>Business names and logos belong to their owners if real</li>
        </ul>
      </div>

    </div>
  </div>
</section>

{{-- ── THEME PURCHASES ─────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="terms-store-heading">
  <div class="container wide">
    <p class="eyebrow">Store</p>
    <h2 id="terms-store-heading">Theme purchases</h2>
    <div class="legal-prose">
      <p>These terms apply when you buy a theme from this site, such as Cobble &amp; Candle. They sit alongside the <a href="{{ home_url('/license/') }}">license</a> and the <a href="{{ home_url('/refunds/') }}">refund policy</a>. By buying or using a theme you agree to all three.</p>

      <h3>Who you are dealing with</h3>
      <p>The themes are made and supported by Matt Hummel, a sole proprietor in Gettysburg, Pennsylvania. Purchases are processed by <strong>Freemius, Inc.</strong>, which is the merchant of record: it takes the payment, issues the license key, and collects any sales tax or VAT. <a href="https://freemius.com/terms/" rel="noopener" target="_blank">Freemius's terms</a> and <a href="https://freemius.com/privacy/" rel="noopener" target="_blank">privacy policy</a> apply to checkout and billing.</p>

      <h3>Your license key</h3>
      <p>Keep your license key and your Freemius login private. A key activates the theme on the number of sites your plan allows. If you reach the limit, deactivate an old site in your account before you activate a new one.</p>

      <h3>Updates and support</h3>
      <ul class="legal-bullets">
        <li>Updates and email support are included for the length of your plan. If it lapses, your sites keep working and you stop getting updates and support until you renew.</li>
        <li>Support covers installing and setting up the theme, bugs in the theme, and questions the docs do not answer.</li>
        <li>It does not cover custom development, conflicts with third-party plugins beyond pointing you to the plugin's own support, or hosting problems.</li>
        <li>I reply to support emails within one business day, Eastern Time.</li>
      </ul>

      <h3>Compatibility</h3>
      <p>Each theme lists the WordPress and PHP versions it needs and was tested on, in its readme and on its product page. Anything outside those versions may work, but it is not guaranteed.</p>

      <h3>No warranty, and a cap on liability</h3>
      <p>Themes are provided as-is, as GPL software is. I fix the bugs I can reproduce, but I do not promise a theme will meet every need or work with every plugin. To the extent the law allows, my total liability for anything related to a theme purchase is limited to the amount you paid in the past 12 months.</p>
    </div>
  </div>
</section>

{{-- ── ACCEPTABLE USE ────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="terms-use-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Acceptable use</p>
      <h2 id="terms-use-heading">Acceptable use</h2>
      <p>By accessing this site you agree not to:</p>
      <ul class="legal-bullets">
        <li>Scrape or harvest content in a way that places excessive load on the server</li>
        <li>Use the contact form to send unsolicited commercial messages (spam)</li>
        <li>Attempt to gain unauthorised access to any part of the site or its hosting infrastructure</li>
        <li>Use this site in a way that violates any applicable law or regulation</li>
        <li>Impersonate Matt Hummel or misrepresent affiliation with this site</li>
      </ul>
      <p>Violation of these terms may result in your IP being blocked from accessing the site.</p>
    </div>
  </div>
</section>

{{-- ── DISCLAIMERS ──────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="terms-disclaimer-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Disclaimers</p>
      <h2 id="terms-disclaimer-heading">No warranty</h2>
      <p>This site and all its content — including code, articles, and examples — are provided <strong>"as is"</strong> without warranty of any kind, express or implied.</p>
      <ul class="legal-bullets">
        <li>I make no guarantee that the site will be available, error-free, or secure at all times.</li>
        <li>Code examples are provided for educational purposes. Test before deploying to production.</li>
        <li>Links to external sites are provided for convenience. I am not responsible for the content or privacy practices of those sites.</li>
        <li>Information may become outdated as WordPress, Sage, and related tools evolve.</li>
      </ul>
    </div>
  </div>
</section>

{{-- ── LIMITATION OF LIABILITY ──────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="terms-liability-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Liability</p>
      <h2 id="terms-liability-heading">Limitation of liability</h2>
      <p>To the fullest extent permitted by law, Matt Hummel shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of this site or its content, including but not limited to loss of data, loss of profits, or business interruption.</p>
      <p>This limitation applies regardless of the cause of action and even if advised of the possibility of such damages.</p>
    </div>
  </div>
</section>

{{-- ── GOVERNING LAW ────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="terms-law-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Jurisdiction</p>
      <h2 id="terms-law-heading">Governing law</h2>
      <p>These terms are governed by the laws of the Commonwealth of Pennsylvania, United States, without regard to conflict of law principles. Any disputes shall be subject to the exclusive jurisdiction of the courts located in Adams County, Pennsylvania.</p>
    </div>
  </div>
</section>

{{-- ── CHANGES ──────────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="terms-changes-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Updates</p>
      <h2 id="terms-changes-heading">Changes to these terms</h2>
      <p>These terms may be updated when the site changes — for example, if new content types or services are added. The "Last updated" date reflects the most recent revision. Continued use of the site after an update constitutes acceptance of the new terms.</p>
    </div>
  </div>
</section>

{{-- ── CONTACT ──────────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="terms-contact-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Questions</p>
      <h2 id="terms-contact-heading">Questions</h2>
      <p>Questions about these terms or any data matters can be sent via the <a href="{{ $contactUrl }}">contact form</a>. See also the <a href="{{ esc_url($privacyUrl) }}">privacy policy</a> for how personal data is handled.</p>
      <div style="margin-top:1.25rem">
        <a class="btn" href="{{ $contactUrl }}">
          {!! \App\mh_svg_icon('mail', 16) !!} Get in touch
        </a>
      </div>
    </div>
  </div>
</section>

@endsection
