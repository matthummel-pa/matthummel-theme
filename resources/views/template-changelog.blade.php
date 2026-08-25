{{--
  Template Name: Changelog
--}}
@extends('layouts.app')

@php
  // Each entry: [version, date, type ('added'|'changed'|'fixed'|'removed'), items[]]
  $entries = [
    [
      'version' => '3.1.x',
      'date'    => '2026-08-25',
      'label'   => 'Current',
      'changes' => [
        ['added',   'Accessibility statement page — WCAG 2.1 AA and Section 508 conformance, 12 features documented, known limitations, opt-out instructions'],
        ['added',   'Privacy policy — covers Google Analytics 4, Google Tag Manager, HubSpot, Meta Pixel, Microsoft/Bing UET, server logs, GDPR rights'],
        ['added',   'Terms of use — MIT licence for code snippets, copyright for writing, concept site disclaimers'],
        ['added',   'This changelog page'],
        ['added',   'Hire page — focused landing for new work enquiries'],
        ['added',   'Uses page — tech stack and tools reference'],
        ['added',   'Thank you page — post-form redirect for conversion tracking'],
        ['added',   'Footer bottom bar: copyright left · Privacy · Terms · Accessibility centre · Built with right'],
        ['changed', 'Services page: pricing section removed, commitment strip, audience cards with fit bullets, mid-page CTA, work teaser, FAQ JSON-LD schema'],
        ['changed', 'Home hero: h1 smaller, profile photo removed, copy wider'],
        ['fixed',   'WCAG 1.4.3: --color-text-muted contrast raised from 2.49:1 to 4.63:1'],
        ['fixed',   'WCAG 1.4.1: post body links now underlined in addition to colour'],
        ['fixed',   'WCAG 2.4.7: form focus outline now uses :focus-visible'],
        ['fixed',   'Mobile menu inert attribute added alongside aria-hidden'],
        ['fixed',   'Concept images on home page and /projects/ — were rendering as http://filename.jpg'],
      ],
    ],
    [
      'version' => '3.1.2',
      'date'    => '2026-08-24',
      'changes' => [
        ['changed', 'Single post redesign — SEO, readability, modern UX across content-single.blade.php and post-sidebar.blade.php'],
        ['changed', 'Category-aware post-end CTA: WordPress posts get "Working on a web project?", Power Platform posts get "Building something with Power Platform?"'],
      ],
    ],
    [
      'version' => '3.1.1',
      'date'    => '2026-08-24',
      'changes' => [
        ['changed', 'Complete visual redesign — minimalist design system, cleaner typography, consistent spacing across all pages'],
        ['changed', 'Home: section anchors, jump-nav pills, live GitHub API panel, skills ticker, back-to-top link'],
        ['changed', 'About: stats bar, open-for-work signals, audience cards, approach grid, journal preview'],
        ['changed', 'Services: numbered principles, tighter process steps, improved FAQs'],
        ['changed', 'Code: GitHub profile showcase redesign, mobile padding fixed'],
        ['changed', 'Contact and Now: UX/SEO overhaul, cleaner form, footer and header improvements'],
      ],
    ],
    [
      'version' => '3.1.0',
      'date'    => '2026-08-24',
      'changes' => [
        ['removed', 'Dark mode toggle and all html.mh-dark CSS (~16 KB removed) — site is now light mode only'],
        ['changed', 'app.css: added color-scheme: light declaration'],
        ['changed', 'app.js: dark mode, blob animation, and theme sync functions removed — JS bundle ~2 KB smaller'],
      ],
    ],
    [
      'version' => '3.0.23',
      'date'    => '2026-08-24',
      'changes' => [
        ['added',   'WP-CLI wp mh db-pull — export production DB via SSH, import locally, search-replace URLs'],
        ['added',   'WP-CLI wp mh db-push — export local DB, send to production, import and search-replace (--yes required)'],
        ['added',   'CI: SiteGround secrets validation step in deploy.yml'],
      ],
    ],
    [
      'version' => '3.0.22',
      'date'    => '2026-08-23',
      'changes' => [
        ['changed', 'Writing renamed to Journal throughout nav, page title, home, and SEO (URL stays /blog/)'],
        ['changed', 'Journal landing: hero search, sort, year and tag browse, most-discussed, numbered pagination, two-column tools rail'],
      ],
    ],
    [
      'version' => '3.0.11–3.0.21',
      'date'    => '2026-08-20 – 2026-08-23',
      'changes' => [
        ['changed', 'SEO playbook applied to all landing pages — Gettysburg keyword density, document titles, meta descriptions'],
        ['changed', 'Home CTAs consolidated — one filled button, one ghost link'],
        ['changed', 'Dark mode WCAG contrast pass — lighter links, distinct muted text, visible borders'],
        ['changed', 'Mobile header: sticky removed, moon/sun toggle, sheet with compact socials'],
        ['changed', 'Comments: ASCII markdown, write/preview tabs, reply notifications'],
        ['changed', 'Code snippets: VS Code Dark+ windows, highlight.js, copy button'],
        ['changed', 'Gutenberg disabled on pages; posts keep the block editor'],
        ['added',   'GitHub Release theme update — Appearance → Update Theme installs latest zip over HTTPS'],
        ['added',   'Theme updater WP-CLI commands: wp mh theme-update, wp mh theme-build'],
      ],
    ],
    [
      'version' => '3.0.0',
      'date'    => '2026-08-19',
      'changes' => [
        ['added',   'Sage 11.2.1 (Blade, Tailwind v4, Vite, Acorn) replacing Pressroot-era theme'],
        ['added',   'Portfolio pages: Home, About, Work, Services, Code, Journal, Contact, Now'],
        ['added',   'Plugin-free contact form with nonce and honeypot'],
        ['added',   'GitHub user/repo helper with transient caching and DEV.to feed'],
        ['added',   'One-time page and menu seed — never deletes existing posts'],
        ['added',   'GitHub Actions CI and deploy to SiteGround on main push'],
        ['removed', 'Pressroot/Kadence modules, theme options, pattern library, custom blocks'],
        ['removed', 'Bud, yarn.lock, Blade Icons'],
      ],
    ],
  ];

  $typeLabels = [
    'added'   => 'Added',
    'changed' => 'Changed',
    'fixed'   => 'Fixed',
    'removed' => 'Removed',
  ];
@endphp

@section('content')

@component('partials.page-hero')
  <p class="eyebrow">Changelog</p>
  <h1 class="display-title is-hero">What's changed.</h1>
  <p class="lead">A public record of notable updates to matthummel.com — new pages, design changes, bug fixes, and anything worth knowing about.</p>
@endcomponent

<section class="pf-section" aria-label="Changelog entries">
  <div class="container wide">
    <div class="cl-feed">
      @foreach ($entries as $entry)
        <article class="cl-entry" aria-labelledby="cl-{{ \Str::slug($entry['version']) }}">
          <div class="cl-entry__meta">
            <span class="cl-version" id="cl-{{ \Str::slug($entry['version']) }}">{{ $entry['version'] }}</span>
            @if (! empty($entry['label']))
              <span class="cl-label">{{ $entry['label'] }}</span>
            @endif
            <time class="cl-date" datetime="{{ $entry['date'] }}">{{ date('M j, Y', strtotime($entry['date'])) }}</time>
          </div>
          <ul class="cl-changes">
            @foreach ($entry['changes'] as [$type, $text])
              <li class="cl-change cl-change--{{ $type }}">
                <span class="cl-change__badge" aria-label="{{ $typeLabels[$type] ?? $type }}">{{ $typeLabels[$type] ?? $type }}</span>
                <span class="cl-change__text">{{ $text }}</span>
              </li>
            @endforeach
          </ul>
        </article>
      @endforeach
    </div>

    <div class="cl-footer">
      <p>Full commit history lives on <a href="https://github.com/matthummel-pa/matthummel-theme" rel="noopener" target="_blank">GitHub</a>. Earlier versions are in the git log.</p>
    </div>
  </div>
</section>

@endsection
