{{--
  Template Name: Privacy Policy
--}}
@extends('layouts.app')

@php
  $updated    = '2026-08-25';
  $contactUrl = home_url('/contact/');
  $siteUrl    = 'https://matthummel.com';
@endphp

@section('content')

@component('partials.page-hero')
  <p class="eyebrow">Legal</p>
  <h1 class="display-title is-hero">Privacy policy.</h1>
  <p class="lead">What data this site collects, how it is used, and what your rights are. Plain language — no legalese.</p>
@endcomponent

{{-- ── OVERVIEW ─────────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="priv-overview-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Overview</p>
      <h2 id="priv-overview-heading">The short version</h2>
      <ul class="legal-bullets">
        <li>This site does not run advertising, analytics, or tracking pixels.</li>
        <li>No third-party cookies are set by this site.</li>
        <li>The contact form collects your name and email so I can reply. That is all it is used for.</li>
        <li>No data is sold or shared with anyone.</li>
        <li>You can request deletion of anything I have about you by <a href="{{ $contactUrl }}">writing to me</a>.</li>
      </ul>
      <p class="legal-meta">Last updated: <time datetime="{{ $updated }}">{{ date('F j, Y', strtotime($updated)) }}</time></p>
    </div>
  </div>
</section>

{{-- ── WHO I AM ─────────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="priv-who-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">About this site</p>
      <h2 id="priv-who-heading">Who I am</h2>
      <p>This website — <strong>matthummel.com</strong> — is a personal portfolio and blog run by <strong>Matt Hummel</strong>, an independent WordPress developer based in Gettysburg, Pennsylvania. It is not operated by a company.</p>
      <p>For questions about this policy or any data I hold, <a href="{{ $contactUrl }}">contact me directly</a>.</p>
    </div>
  </div>
</section>

{{-- ── DATA COLLECTED ──────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="priv-data-heading">
  <div class="container wide">
    <p class="eyebrow">Data collected</p>
    <h2 id="priv-data-heading">What I collect</h2>
    <p class="sec-intro" style="max-width:52ch;margin-bottom:2rem">Three categories of data may be collected when you use this site.</p>
    <div class="legal-data-grid">

      <div class="legal-data-card">
        <h3 class="legal-data-card__title">Contact form</h3>
        <p class="legal-data-card__intro">When you submit the contact form, I collect:</p>
        <ul class="legal-data-card__list">
          <li><strong>Name</strong> — so I know how to address you</li>
          <li><strong>Email address</strong> — so I can reply</li>
          <li><strong>Message content</strong> — the inquiry itself</li>
          <li><strong>Subject / who you are</strong> — optional fields</li>
        </ul>
        <p class="legal-data-card__note">This data is used only to respond to your message. It is not added to any mailing list or shared with anyone.</p>
      </div>

      <div class="legal-data-card">
        <h3 class="legal-data-card__title">Comments</h3>
        <p class="legal-data-card__intro">If you leave a comment on a blog post, I collect:</p>
        <ul class="legal-data-card__list">
          <li><strong>Name</strong> — displayed with the comment</li>
          <li><strong>Email address</strong> — used only to send reply notifications if you opt in</li>
          <li><strong>Comment text</strong> — displayed publicly</li>
        </ul>
        <p class="legal-data-card__note">Comment email addresses are never displayed publicly. You can request removal of a comment at any time.</p>
      </div>

      <div class="legal-data-card">
        <h3 class="legal-data-card__title">Server logs</h3>
        <p class="legal-data-card__intro">The hosting server automatically logs standard request data:</p>
        <ul class="legal-data-card__list">
          <li>IP address</li>
          <li>Browser type and version</li>
          <li>Page requested and date/time</li>
          <li>HTTP referrer</li>
        </ul>
        <p class="legal-data-card__note">These logs are retained by SiteGround (the hosting provider) for security and performance purposes. I do not review them routinely and do not use them to track individuals.</p>
      </div>

    </div>
  </div>
</section>

{{-- ── WHAT I DO NOT COLLECT ────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="priv-no-collect-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Not collected</p>
      <h2 id="priv-no-collect-heading">What I do not collect</h2>
      <ul class="legal-bullets">
        <li>No analytics (no Google Analytics, no Plausible, no Fathom, no tracking pixels)</li>
        <li>No advertising cookies or retargeting data</li>
        <li>No payment data (this site has no e-commerce)</li>
        <li>No account or login data (there is no user registration on this site)</li>
        <li>No social media tracking scripts from third parties</li>
      </ul>
    </div>
  </div>
</section>

{{-- ── COOKIES ──────────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="priv-cookies-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Cookies</p>
      <h2 id="priv-cookies-heading">Cookies</h2>
      <p>This site sets a small number of functional cookies. No advertising or tracking cookies are used.</p>
    </div>
    <div class="legal-table-wrap">
      <table class="legal-table">
        <caption class="visually-hidden">Cookies set by this site</caption>
        <thead>
          <tr>
            <th scope="col">Cookie</th>
            <th scope="col">Purpose</th>
            <th scope="col">Duration</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>comment_author_*</code></td>
            <td>Remembers your name and email if you leave a comment, so you do not have to re-enter them</td>
            <td>1 year</td>
          </tr>
          <tr>
            <td><code>wordpress_test_cookie</code></td>
            <td>Checks that your browser accepts cookies (set by WordPress on login only)</td>
            <td>Session</td>
          </tr>
          <tr>
            <td><code>mh-theme</code></td>
            <td>Remembers your light/dark mode preference if you toggle it</td>
            <td>Local storage (not a cookie)</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="legal-prose" style="margin-top:1.25rem">
      <p>You can block or delete cookies through your browser settings. Blocking functional cookies may affect comment submission.</p>
    </div>
  </div>
</section>

{{-- ── DATA SHARING ──────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="priv-sharing-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Data sharing</p>
      <h2 id="priv-sharing-heading">Who sees your data</h2>
      <p>Your data is not sold, rented, or shared with third parties for marketing purposes. It may reach the following parties in the course of normal operation:</p>
      <ul class="legal-bullets">
        <li><strong>SiteGround</strong> — the web hosting provider that stores the site database and files. <a href="https://www.siteground.com/gdpr" rel="noopener" target="_blank">SiteGround's privacy policy</a>.</li>
        <li><strong>GitHub</strong> — this site reads public profile and repository data from the GitHub API to display on the Code page. No personal visitor data is sent to GitHub. <a href="https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement" rel="noopener" target="_blank">GitHub's privacy policy</a>.</li>
        <li><strong>Law enforcement</strong> — if required by a valid legal process, data may be disclosed as required by law.</li>
      </ul>
    </div>
  </div>
</section>

{{-- ── YOUR RIGHTS ──────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="priv-rights-heading">
  <div class="container wide">
    <p class="eyebrow">Your rights</p>
    <h2 id="priv-rights-heading">Your rights over your data</h2>
    <p class="sec-intro" style="max-width:52ch;margin-bottom:2rem">Under GDPR (if you are in the EU/UK) and comparable laws, you have the following rights. To exercise any of them, <a href="{{ $contactUrl }}">write to me</a>.</p>
    <div class="legal-rights-grid">
      @foreach ([
        ['Access', 'Request a copy of any personal data I hold about you.'],
        ['Correction', 'Ask me to correct inaccurate or incomplete data.'],
        ['Deletion', 'Request deletion of your personal data ("right to be forgotten"). I will comply within 30 days unless retention is required by law.'],
        ['Portability', 'Receive your data in a structured, machine-readable format.'],
        ['Objection', 'Object to processing of your data for any purpose.'],
        ['Withdrawal', 'Withdraw consent at any time where processing is based on consent.'],
      ] as [$right, $desc])
        <div class="legal-right-card">
          <h3 class="legal-right-card__title">{{ $right }}</h3>
          <p class="legal-right-card__body">{{ $desc }}</p>
        </div>
      @endforeach
    </div>
    <div class="legal-prose" style="margin-top:1.75rem">
      <p>If you believe your rights have not been respected, you have the right to lodge a complaint with your national data protection authority.</p>
    </div>
  </div>
</section>

{{-- ── CHANGES ──────────────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="priv-changes-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Updates</p>
      <h2 id="priv-changes-heading">Changes to this policy</h2>
      <p>This policy may be updated when the site changes substantially — for example, if a new form or third-party service is added. The "Last updated" date at the top of this page reflects the most recent revision.</p>
      <p>I will not reduce your rights under this policy without notifying you.</p>
    </div>
  </div>
</section>

{{-- ── CONTACT ──────────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="priv-contact-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Questions</p>
      <h2 id="priv-contact-heading">Questions about this policy</h2>
      <p>If you have questions about this privacy policy or want to exercise any of your data rights, the fastest way is to <a href="{{ $contactUrl }}">use the contact form</a>. I reply within two business days.</p>
      <div style="margin-top:1.25rem">
        <a class="btn" href="{{ $contactUrl }}">
          {!! \App\mh_svg_icon('mail', 16) !!} Get in touch
        </a>
      </div>
    </div>
  </div>
</section>

@endsection
