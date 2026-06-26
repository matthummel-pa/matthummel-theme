{{--
  Template Name: About
--}}

@extends('layouts.app')

@section('content')
@php
  // Pull content sections from page content (optional)
  $content = get_the_content();
@endphp

{{-- ── INTRO HERO ─────────────────────────────────────────────────── --}}
<section class="about-hero container" data-anim="fade-up">
  <div class="about-hero-inner">
    <div class="about-hero-text">
      <p class="eyebrow">{{ __('Web Developer · Gettysburg, PA', 'matthummel') }}</p>
      <h1 class="display-title is-hero">{{ get_the_title() }}</h1>
      <div class="post-prose">
        @php(the_content())
      </div>
    </div>
    @php($aboutImg = get_theme_mod('mh_about_photo', ''))
    @if ($aboutImg)
      <div class="about-hero-photo">
        <img src="{{ esc_url($aboutImg) }}" alt="{{ get_the_title() }}" loading="lazy">
      </div>
    @endif
  </div>
</section>

<hr class="rule">

{{-- ── STAT COUNTERS ──────────────────────────────────────────────── --}}
<section class="about-stats container">
  <div class="stat-grid">
    <div class="stat-item">
      <span class="stat-number">15+</span>
      <span class="stat-label">{{ __('Years in web dev', 'matthummel') }}</span>
    </div>
    <div class="stat-item">
      <span class="stat-number">Front→Back</span>
      <span class="stat-label">{{ __('Full-stack skills', 'matthummel') }}</span>
    </div>
    <div class="stat-item">
      <span class="stat-number">2</span>
      <span class="stat-label">{{ __('Platforms: Web + M365', 'matthummel') }}</span>
    </div>
    <div class="stat-item">
      <span class="stat-number">100%</span>
      <span class="stat-label">{{ __('Remote-friendly', 'matthummel') }}</span>
    </div>
  </div>
</section>

<hr class="rule">

{{-- ── WHAT I DO (3-column skills) ───────────────────────────────── --}}
<section class="about-section container" data-anim="fade-up">
  <div class="section-head">
    <h2 class="display-title is-section">{{ __('What I do', 'matthummel') }}</h2>
  </div>
  <div class="skills-grid">
    <div class="skill-card">
      <h3>{{ __('Front-End Development', 'matthummel') }}</h3>
      <p>{{ __('React, Tailwind CSS, TypeScript, WordPress block themes, accessible HTML & CSS — building interfaces that load fast and look great.', 'matthummel') }}</p>
    </div>
    <div class="skill-card">
      <h3>{{ __('Back-End Development', 'matthummel') }}</h3>
      <p>{{ __('PHP, WordPress plugin & theme development, REST APIs, Node.js, PostgreSQL/Supabase — building the systems that power the front.', 'matthummel') }}</p>
    </div>
    <div class="skill-card">
      <h3>{{ __('Performance & Accessibility', 'matthummel') }}</h3>
      <p>{{ __('Core Web Vitals, WCAG 2.1 AA compliance, Lighthouse optimization, and SEO foundations baked in from the start.', 'matthummel') }}</p>
    </div>
  </div>
</section>

<hr class="rule">

{{-- ── WHAT I FOCUS ON (2-column) ─────────────────────────────────── --}}
<section class="about-section container" data-anim="fade-up">
  <div class="section-head">
    <h2 class="display-title is-section">{{ __('What I focus on', 'matthummel') }}</h2>
  </div>
  <div class="focus-grid">
    <div class="focus-card">
      <h3>{{ __('WordPress Development', 'matthummel') }}</h3>
      <p>{{ __('Custom themes (Sage 10 / Roots), plugin development, Gutenberg blocks, performance tuning, and headless WordPress setups.', 'matthummel') }}</p>
    </div>
    <div class="focus-card">
      <h3>{{ __('Microsoft Power Platform', 'matthummel') }}</h3>
      <p>{{ __('Power Apps canvas apps, Power Automate flows, SharePoint integration, and M365 business process automation.', 'matthummel') }}</p>
    </div>
  </div>
</section>

<hr class="rule">

{{-- ── HOW I GOT HERE (bio timeline) ─────────────────────────────── --}}
<section class="about-section about-bio container" data-anim="fade-up">
  <div class="section-head">
    <h2 class="display-title is-section">{{ __('How I got here', 'matthummel') }}</h2>
  </div>
  <div class="bio-content post-prose">
    <h3>🌐 {{ __('Rooted in web development', 'matthummel') }}</h3>
    <p>{{ __("I started building websites in the early 2000s — hand-coding HTML tables before CSS layouts existed. That foundation turned into a 15+ year career spanning everything from agency work to in-house development roles.", 'matthummel') }}</p>
    <h3>🚀 {{ __('Full-stack, plus the Microsoft stack', 'matthummel') }}</h3>
    <p>{{ __("A few years ago I added Microsoft Power Platform to my toolkit. Now I spend time in both worlds — building public-facing WordPress sites and internal M365 business apps, often for the same organizations.", 'matthummel') }}</p>
  </div>
</section>

@include('partials.cta')
@endsection
