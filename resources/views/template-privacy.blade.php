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
        <li>This site uses <strong>Google Analytics</strong>, <strong>Google Tag Manager</strong>, <strong>HubSpot</strong>, <strong>Bing / Microsoft Analytics</strong>, and plans to use the <strong>Meta (Facebook) Pixel</strong> for audience measurement, CRM activity, and ad performance.</li>
        <li>The contact form collects your name and email so I can reply. That is all it is used for.</li>
        <li>Analytics data is aggregated and anonymised — it is not used to identify you personally.</li>
        <li>No data is sold or shared with anyone outside the services listed in this policy.</li>
        <li>You can opt out of analytics tracking at any time — see the <a href="#priv-optout-heading">opt-out options</a> below.</li>
        <li>You can request deletion of any personal data by <a href="{{ $contactUrl }}">writing to me</a>.</li>
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
    <p class="sec-intro" style="max-width:52ch;margin-bottom:2rem">Four categories of data may be collected when you use this site.</p>
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
        <h3 class="legal-data-card__title">Analytics and advertising</h3>
        <p class="legal-data-card__intro">This site uses the following third-party tools to understand site traffic and measure advertising performance:</p>
        <ul class="legal-data-card__list">
          <li><strong>Google Analytics 4</strong> — page views, session duration, referrer, device type</li>
          <li><strong>Google Tag Manager</strong> — manages the above tracking scripts</li>
          <li><strong>Meta (Facebook) Pixel</strong> — ad reach and conversion measurement (planned)</li>
          <li><strong>HubSpot</strong> — visitor tracking, contact form capture, and CRM activity</li>
          <li><strong>Bing / Microsoft</strong> — Bing Webmaster Tools and Microsoft UET (Universal Event Tracking) for search analytics and ad conversion measurement</li>
        </ul>
        <p class="legal-data-card__note">These tools set cookies and may send your IP address and browsing behaviour to Google and Meta. See <a href="#priv-optout-heading">opt-out options</a> below.</p>
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
        <li>No payment data — this site has no e-commerce or checkout</li>
        <li>No account or login data — there is no user registration on this site</li>
        <li>No sensitive personal data (health, financial, biometric, or similar)</li>
        <li>Analytics data is not used to build individual profiles or target you personally on this site</li>
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
      <p>This site sets functional cookies and third-party analytics cookies. The table below lists all cookies in use.</p>
    </div>
    <div class="legal-table-wrap">
      <table class="legal-table">
        <caption class="visually-hidden">Cookies set by this site and third parties</caption>
        <thead>
          <tr>
            <th scope="col">Cookie</th>
            <th scope="col">Set by</th>
            <th scope="col">Purpose</th>
            <th scope="col">Duration</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>comment_author_*</code></td>
            <td>This site</td>
            <td>Remembers your name and email after leaving a comment</td>
            <td>1 year</td>
          </tr>
          <tr>
            <td><code>wordpress_test_cookie</code></td>
            <td>This site</td>
            <td>Checks that your browser accepts cookies (admin login only)</td>
            <td>Session</td>
          </tr>
          <tr>
            <td><code>mh-theme</code></td>
            <td>This site</td>
            <td>Stores your light/dark mode preference</td>
            <td>localStorage — no expiry</td>
          </tr>
          <tr>
            <td><code>_ga</code>, <code>_ga_*</code></td>
            <td>Google Analytics</td>
            <td>Distinguishes unique visitors; tracks sessions, pages, and referrers</td>
            <td>2 years</td>
          </tr>
          <tr>
            <td><code>_gid</code></td>
            <td>Google Analytics</td>
            <td>Stores and updates a unique value for each page visited</td>
            <td>24 hours</td>
          </tr>
          <tr>
            <td><code>_fbp</code></td>
            <td>Meta (Facebook) Pixel</td>
            <td>Identifies browsers for ad delivery and measurement (planned)</td>
            <td>3 months</td>
          </tr>
          <tr>
            <td><code>hubspotutk</code></td>
            <td>HubSpot</td>
            <td>Tracks a visitor's identity across sessions and associates form submissions with browsing history</td>
            <td>13 months</td>
          </tr>
          <tr>
            <td><code>__hstc</code></td>
            <td>HubSpot</td>
            <td>Main cookie for tracking visitors — stores session count, timestamps, and original referrer</td>
            <td>13 months</td>
          </tr>
          <tr>
            <td><code>__hssc</code></td>
            <td>HubSpot</td>
            <td>Keeps track of sessions for analytics purposes</td>
            <td>30 minutes</td>
          </tr>
          <tr>
            <td><code>__hssrc</code></td>
            <td>HubSpot</td>
            <td>Determines whether the browser has been restarted between sessions</td>
            <td>Session</td>
          </tr>
          <tr>
            <td><code>_uetsid</code>, <code>_uetvid</code></td>
            <td>Microsoft UET</td>
            <td>Tracks sessions and unique visitors for Bing Ads conversion measurement</td>
            <td>1 day / 16 days</td>
          </tr>
          <tr>
            <td><code>MUID</code></td>
            <td>Microsoft</td>
            <td>Identifies unique web browsers visiting Microsoft sites and services</td>
            <td>13 months</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="legal-prose" style="margin-top:1.25rem">
      <p>You can block or delete cookies through your browser settings. Blocking third-party cookies disables analytics tracking. Blocking functional cookies may affect comment submission.</p>
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
        <li><strong>Google</strong> — Google Analytics 4 and Google Tag Manager receive anonymised usage data (page views, session data, device type, approximate location). Subject to <a href="https://policies.google.com/privacy" rel="noopener" target="_blank">Google's privacy policy</a>. IP anonymisation is enabled.</li>
        <li><strong>Meta (Facebook)</strong> — the Meta Pixel (planned) sends event data to Meta for ad measurement and reach reporting. Subject to <a href="https://www.facebook.com/privacy/policy/" rel="noopener" target="_blank">Meta's privacy policy</a>.</li>
        <li><strong>HubSpot</strong> — HubSpot tracking is active on this site. It collects IP address, browser and device data, pages visited, and form submissions to support CRM and marketing activity. If you submit the contact form, your name and email may be stored in HubSpot. Subject to <a href="https://legal.hubspot.com/privacy-policy" rel="noopener" target="_blank">HubSpot's privacy policy</a>.</li>
        <li><strong>Microsoft (Bing)</strong> — Bing Webmaster Tools and the Microsoft UET tag collect aggregated search performance data and conversion events. Data is subject to <a href="https://privacy.microsoft.com/en-us/privacystatement" rel="noopener" target="_blank">Microsoft's privacy statement</a>.</li>
        <li><strong>SiteGround</strong> — the web hosting provider stores the site database and files. <a href="https://www.siteground.com/gdpr" rel="noopener" target="_blank">SiteGround's privacy policy</a>.</li>
        <li><strong>GitHub</strong> — this site reads public data from the GitHub API to display on the Code page. No personal visitor data is sent to GitHub. <a href="https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement" rel="noopener" target="_blank">GitHub's privacy policy</a>.</li>
        <li><strong>Law enforcement</strong> — data may be disclosed if required by a valid legal process.</li>
      </ul>
    </div>
  </div>
</section>

{{-- ── OPT-OUT ──────────────────────────────────────────── --}}
<section class="pf-section" aria-labelledby="priv-optout-heading">
  <div class="container wide">
    <div class="legal-prose">
      <p class="eyebrow">Opt out</p>
      <h2 id="priv-optout-heading">How to opt out of analytics</h2>
      <p>You have several options for limiting or disabling analytics tracking on this site:</p>
    </div>
    <div class="legal-data-grid" style="margin-top:1.25rem">

      <div class="legal-data-card">
        <h3 class="legal-data-card__title">Google Analytics opt-out</h3>
        <ul class="legal-data-card__list">
          <li>Install the <a href="https://tools.google.com/dlpage/gaoptout" rel="noopener" target="_blank">Google Analytics Opt-out Browser Add-on</a></li>
          <li>Enable "Do Not Track" in your browser settings (honoured on a best-effort basis)</li>
          <li>Block <code>*.google-analytics.com</code> via an ad blocker or browser extension</li>
        </ul>
      </div>

      <div class="legal-data-card">
        <h3 class="legal-data-card__title">Meta Pixel opt-out</h3>
        <ul class="legal-data-card__list">
          <li>Use the <a href="https://www.facebook.com/help/568137493302217" rel="noopener" target="_blank">Facebook Ad Preferences</a> to limit off-Facebook tracking</li>
          <li>Install the <a href="https://www.facebook.com/help/247395082112892" rel="noopener" target="_blank">Facebook Container</a> browser extension</li>
          <li>Block <code>*.facebook.com</code> via an ad blocker</li>
        </ul>
      </div>

      <div class="legal-data-card">
        <h3 class="legal-data-card__title">Microsoft / Bing opt-out</h3>
        <ul class="legal-data-card__list">
          <li>Use <a href="https://choice.microsoft.com" rel="noopener" target="_blank">Microsoft's privacy choice tool</a> to opt out of personalised advertising</li>
          <li>Block <code>*.bat.bing.com</code> and <code>*.clarity.ms</code> via an ad blocker</li>
          <li>Delete Microsoft cookies (<code>_uetsid</code>, <code>_uetvid</code>, <code>MUID</code>) from your browser</li>
        </ul>
      </div>

      <div class="legal-data-card">
        <h3 class="legal-data-card__title">HubSpot opt-out</h3>
        <ul class="legal-data-card__list">
          <li>Block <code>*.hs-scripts.com</code>, <code>*.hubspot.com</code>, and <code>*.hsforms.com</code> via an ad blocker</li>
          <li>Delete HubSpot cookies (<code>hubspotutk</code>, <code>__hstc</code>, <code>__hssc</code>, <code>__hssrc</code>) from your browser</li>
          <li>Submit a data deletion request via <a href="{{ $contactUrl }}">the contact form</a> — I will forward it to HubSpot within 5 business days</li>
        </ul>
      </div>

      <div class="legal-data-card">
        <h3 class="legal-data-card__title">Browser-level controls</h3>
        <ul class="legal-data-card__list">
          <li>Block third-party cookies in your browser settings</li>
          <li>Use a privacy-focused browser (Firefox, Brave, Safari) with enhanced tracking protection enabled</li>
          <li>Install an ad/tracker blocker such as uBlock Origin</li>
        </ul>
      </div>

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
