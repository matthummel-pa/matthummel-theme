{{--
  Template Name: Résumé
--}}

@extends('layouts.app')

@section('content')

{{-- ── HEADER ───────────────────────────────────────────────────────── --}}
<header class="resume-header container" data-anim="fade-up">
  <div class="resume-header-inner">
    <div>
      <p class="eyebrow">{{ __('Web Developer · Power Platform Consultant', 'matthummel') }}</p>
      <h1 class="display-title is-hero">{{ __('Matt Hummel', 'matthummel') }}</h1>
      <p class="lead">{{ __('15+ years building web experiences and Microsoft 365 business tools.', 'matthummel') }}</p>
    </div>
    <div class="resume-actions">
      @php($pdfUrl = get_theme_mod('mh_resume_pdf', ''))
      @if ($pdfUrl)
        <a class="btn" href="{{ esc_url($pdfUrl) }}" target="_blank" rel="noopener" download>
          {{ __('Download PDF', 'matthummel') }}
        </a>
      @endif
      <a class="btn btn-outline" href="{{ get_permalink(get_page_by_path('contact')) }}">
        {{ __('Contact me', 'matthummel') }}
      </a>
    </div>
  </div>
</header>

<hr class="rule">

{{-- ── SKILLS SUMMARY ──────────────────────────────────────────────── --}}
<section class="resume-section container" data-anim="fade-up">
  <h2 class="resume-section-title">{{ __('Core Skills', 'matthummel') }}</h2>
  <div class="skills-tag-wrap">
    @php($skills = [
      'HTML / CSS', 'JavaScript', 'TypeScript', 'React', 'PHP', 'WordPress',
      'Sage / Roots', 'Gutenberg Blocks', 'Tailwind CSS', 'Node.js',
      'Power Apps', 'Power Automate', 'SharePoint', 'Microsoft 365',
      'PostgreSQL', 'Supabase', 'Git', 'Vite', 'REST APIs', 'SEO',
    ])
    @foreach ($skills as $skill)
      <span class="tag-pill">{{ $skill }}</span>
    @endforeach
  </div>
</section>

<hr class="rule">

{{-- ── WORK HISTORY TIMELINE ────────────────────────────────────────── --}}
<section class="resume-section container" data-anim="fade-up">
  <h2 class="resume-section-title">{{ __('Experience', 'matthummel') }}</h2>

  <div class="resume-timeline">

    <article class="timeline-entry">
      <div class="timeline-meta">
        <span class="timeline-dates">{{ __('2021 – Present', 'matthummel') }}</span>
      </div>
      <div class="timeline-body">
        <h3>{{ __('Senior Power Platform Consultant', 'matthummel') }}</h3>
        <p class="timeline-org">{{ __('Various clients · Remote', 'matthummel') }}</p>
        <p>{{ __('Design and build Power Apps canvas apps, Power Automate flows, SharePoint solutions, and M365 integrations for enterprise and SMB clients.', 'matthummel') }}</p>
      </div>
    </article>

    <article class="timeline-entry">
      <div class="timeline-meta">
        <span class="timeline-dates">{{ __('2015 – 2021', 'matthummel') }}</span>
      </div>
      <div class="timeline-body">
        <h3>{{ __('Applications & SharePoint Administrator', 'matthummel') }}</h3>
        <p class="timeline-org">{{ __('In-house · Gettysburg, PA', 'matthummel') }}</p>
        <p>{{ __('Managed SharePoint Online environment, built custom intranet pages, maintained internal web applications, and provided Microsoft 365 user support.', 'matthummel') }}</p>
      </div>
    </article>

    <article class="timeline-entry">
      <div class="timeline-meta">
        <span class="timeline-dates">{{ __('2010 – 2015', 'matthummel') }}</span>
      </div>
      <div class="timeline-body">
        <h3>{{ __('SharePoint Web Developer', 'matthummel') }}</h3>
        <p class="timeline-org">{{ __('Agency · Remote', 'matthummel') }}</p>
        <p>{{ __('Custom SharePoint branding, web parts, and client-side solutions using jQuery, HTML/CSS, and early JavaScript frameworks.', 'matthummel') }}</p>
      </div>
    </article>

    <article class="timeline-entry">
      <div class="timeline-meta">
        <span class="timeline-dates">{{ __('2006 – 2010', 'matthummel') }}</span>
      </div>
      <div class="timeline-body">
        <h3>{{ __('Web Developer', 'matthummel') }}</h3>
        <p class="timeline-org">{{ __('Freelance · Self-employed', 'matthummel') }}</p>
        <p>{{ __('Built websites for local businesses using HTML, CSS, PHP, and early WordPress. Foundation for everything that followed.', 'matthummel') }}</p>
      </div>
    </article>

    <article class="timeline-entry">
      <div class="timeline-meta">
        <span class="timeline-dates">{{ __('2015 – Present', 'matthummel') }}</span>
      </div>
      <div class="timeline-body">
        <h3>{{ __('Technical Writing · matthummel.com', 'matthummel') }}</h3>
        <p class="timeline-org">{{ __('Blog · Self-published', 'matthummel') }}</p>
        <p>{{ __('Write beginner-friendly tutorials on WordPress, Power Platform, and web development. Published 20+ articles covering Power Apps, Power Automate, React, CSS, and web performance.', 'matthummel') }}</p>
      </div>
    </article>

  </div>
</section>

@if (get_the_content())
  <hr class="rule">
  <section class="resume-section container post-prose">
    @php(the_content())
  </section>
@endif

@include('partials.cta')

@endsection
