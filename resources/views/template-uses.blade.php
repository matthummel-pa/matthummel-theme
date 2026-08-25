{{--
  Template Name: Uses
--}}
@extends('layouts.app')

@php
  $sections = [
    [
      'title' => 'WordPress development',
      'icon'  => 'wordpress',
      'items' => [
        ['Sage 11', 'The Roots starter theme. Blade templates, Tailwind v4, Vite. Every site I build from scratch starts here.', 'https://roots.io/sage/'],
        ['PHP 8.3', 'The language everything runs on. Typed functions, match expressions, named arguments. Nothing exotic.', null],
        ['Tailwind v4', 'CSS utility framework. Token-based, no config file needed. Fluid type with clamp(), mobile-first always.', 'https://tailwindcss.com'],
        ['Vite 8', 'Asset bundler. Fast, simple config. Handles CSS, JS, and image hashing for cache-busting.', 'https://vitejs.dev'],
        ['Acorn / Laravel', 'The IoC container that makes Sage feel like a proper application. Service providers, Blade directives, view composers.', 'https://roots.io/acorn/'],
        ['WP-CLI', 'Command-line tools for WordPress. Database exports, plugin management, custom commands. Most deploys never touch wp-admin.', 'https://wp-cli.org'],
        ['Composer', 'PHP dependency manager. Every project has a composer.json. No manual library downloads.', 'https://getcomposer.org'],
        ['Laravel Pint', 'PHP code style fixer. Runs in CI before every deploy. Catches whitespace and formatting issues automatically.', 'https://laravel.com/docs/pint'],
      ],
    ],
    [
      'title' => 'Editor and tools',
      'icon'  => 'code',
      'items' => [
        ['Cursor AI', 'My primary editor. VS Code-compatible with an AI layer that actually helps rather than gets in the way. Everything on this site was planned and built with it.', 'https://cursor.com'],
        ['GitHub', 'Version control and CI/CD trigger. Every project lives in a GitHub repo. Pushes to main kick off builds and deploys automatically.', 'https://github.com'],
        ['GitHub Actions', 'Automated build and deploy pipeline. Runs Composer, npm, and rsync on every push. Zero manual uploads.', 'https://github.com/features/actions'],
        ['TablePlus', 'Database GUI for local MySQL and SQLite. Useful for inspecting WordPress tables without writing raw SQL.', 'https://tableplus.com'],
        ['iTerm2 / zsh', 'Terminal. Nothing special — zsh with a minimal prompt. SSH into servers, run WP-CLI, tail logs.', null],
      ],
    ],
    [
      'title' => 'Hosting and deploy',
      'icon'  => 'server',
      'items' => [
        ['SiteGround', 'Managed WordPress hosting for client sites. Solid uptime, decent caching, easy SSH access. PHP 8.3 support is there when you ask for it.', 'https://www.siteground.com'],
        ['GitHub Releases', 'Theme deployment method for this site. CI builds a zip, publishes it as a release, and wp-admin pulls it over HTTPS. No FTP.', null],
        ['SQLite (local)', 'Local WordPress database for development and Cloud Agent environments. No MySQL required, no Docker overhead. The sqlite-database-integration plugin handles it.', null],
      ],
    ],
    [
      'title' => 'Analytics and marketing',
      'icon'  => 'chart-bar',
      'items' => [
        ['Google Analytics 4', 'Site traffic, page performance, and audience data. Linked through Google Tag Manager.', 'https://analytics.google.com'],
        ['Google Tag Manager', 'Single container for all tracking scripts. One snippet on the page, everything else managed in GTM.', 'https://tagmanager.google.com'],
        ['HubSpot', 'CRM and contact capture. Picks up form submissions and tracks visitor activity for follow-up.', 'https://www.hubspot.com'],
        ['Microsoft Clarity / Bing', 'Bing Webmaster Tools for search performance. Microsoft UET for ad conversion tracking.', 'https://clarity.microsoft.com'],
        ['Meta Pixel', 'Facebook and Instagram ad measurement. Planned — not fully active yet.', null],
      ],
    ],
    [
      'title' => 'Design and typography',
      'icon'  => 'full-stack',
      'items' => [
        ['Inter', 'Display and heading font. Clean, reads well at both large and small sizes. The workhorse.', 'https://rsms.me/inter/'],
        ['IBM Plex Sans', 'Body text font. Slightly warmer than Inter. Works well at the 1.1–1.2rem range.', 'https://www.ibm.com/plex/'],
        ['IBM Plex Mono', 'Code font. Used in blog post code blocks and the monospace CSS variable.', 'https://www.ibm.com/plex/'],
        ['Figma', 'Design when a client needs wireframes or component specs before I build. Not a daily tool for me — I prefer designing in the browser.', 'https://www.figma.com'],
        ['highlight.js', 'Syntax highlighting for blog post code blocks. VS Code Dark+ theme. Copy button added via a small custom JS module.', 'https://highlightjs.org'],
      ],
    ],
    [
      'title' => 'Power Platform',
      'icon'  => 'power',
      'items' => [
        ['Power Apps', 'Canvas and model-driven apps for Microsoft 365 environments. Used at previous roles and on client work when it\'s the right tool.', 'https://powerapps.microsoft.com'],
        ['Power Automate', 'Workflow automation. Approval flows, SharePoint triggers, Teams notifications. Useful when a team already lives in M365.', 'https://powerautomate.microsoft.com'],
        ['SharePoint', 'Common data source for Power Apps. Lists, document libraries, and permissions.', null],
      ],
    ],
  ];
@endphp

@section('content')

@component('partials.page-hero')
  <p class="eyebrow">Uses</p>
  <h1 class="display-title is-hero">What I use.</h1>
  <p class="lead">The tools, stack, and services that show up on real projects. Not an exhaustive list — just what I actually reach for.</p>
  <p class="about-hero-links" style="margin-top:1rem">
    @foreach ($sections as $s)
      <a href="#uses-{{ \Str::slug($s['title']) }}">{!! \App\mh_svg_icon($s['icon'], 14) !!} {{ $s['title'] }}</a>
    @endforeach
  </p>
@endcomponent

<div class="uses-body pf-section">
  <div class="container wide">
    @foreach ($sections as $s)
      <section class="uses-section" id="uses-{{ \Str::slug($s['title']) }}" aria-labelledby="uses-{{ \Str::slug($s['title']) }}-heading">
        <div class="uses-section__head">
          <div class="uses-section__icon">{!! \App\mh_svg_icon($s['icon'], 20) !!}</div>
          <h2 id="uses-{{ \Str::slug($s['title']) }}-heading" class="uses-section__title">{{ $s['title'] }}</h2>
        </div>
        <ul class="uses-list">
          @foreach ($s['items'] as [$name, $desc, $url])
            <li class="uses-item">
              <div class="uses-item__name">
                @if ($url)
                  <a href="{{ esc_url($url) }}" rel="noopener" target="_blank">{{ $name }}</a>
                @else
                  {{ $name }}
                @endif
              </div>
              <p class="uses-item__desc">{{ $desc }}</p>
            </li>
          @endforeach
        </ul>
      </section>
    @endforeach
  </div>
</div>

{{-- CTA --}}
<section class="cta-band" aria-labelledby="uses-cta-heading">
  <div class="container wide cta-band-inner">
    <div>
      <p class="eyebrow eyebrow--on-dark">Open for work</p>
      <h2 id="uses-cta-heading" class="display-title is-section">Want to build something?</h2>
      <p>I use this stack on real projects for shops, agencies, and developers. If you have something in mind, <a href="{{ home_url('/contact/') }}" style="color:#93c5fd">say hello</a>.</p>
    </div>
    <div style="display:flex;flex-direction:column;gap:.75rem;align-items:flex-end;flex-shrink:0">
      <a class="btn btn-on-dark" href="{{ home_url('/hire/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!} Hire me
      </a>
      <a class="about-text-link" href="{{ home_url('/code/') }}" style="color:#9ca3af">See my code →</a>
    </div>
  </div>
</section>

@endsection
