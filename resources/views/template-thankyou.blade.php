{{--
  Template Name: Thank You
--}}
@extends('layouts.app')

@php
  $featured = array_slice(\App\mh_work_page_items(), 0, 3);
  $fromStart = isset($_GET['from']) && sanitize_key(wp_unslash($_GET['from'])) === 'start';
@endphp

@section('content')

{{-- ── CONFIRMATION ──────────────────────────────────── --}}
<section class="ty-hero">
  <div class="container wide ty-hero-inner">

    <div class="ty-confirm">
      <div class="ty-confirm__icon" aria-hidden="true">
        {!! \App\mh_svg_icon('check', 26) !!}
      </div>
      <div>
        <h1 class="ty-confirm__heading">{{ $fromStart ? 'Brief received.' : 'Message received.' }}</h1>
        <p class="ty-confirm__lede">
          @if ($fromStart)
            Your project brief is in my inbox. I’ll read it before we talk so the first meeting starts with context, not a blank page. I usually reply within a business day.
          @else
            Your note is in my inbox. I usually reply within a business day — occasionally two if I'm heads-down on a build.
          @endif
        </p>
      </div>
    </div>

    {{-- What happens next --}}
    <div class="ty-next">
      <p class="ty-next__label">What happens next</p>
      <ol class="ty-next__steps">
        <li>
          <span class="ty-next__num">1</span>
          <div>
            <strong>I read your message.</strong>
            <p>Every message comes directly to me — no ticketing system, no assistant. I read the whole thing.</p>
          </div>
        </li>
        <li>
          <span class="ty-next__num">2</span>
          <div>
            <strong>I reply with a real answer.</strong>
            <p>If it's a project enquiry I'll ask a few clarifying questions. If it's a snippet question I'll answer it directly. If I'm not the right fit I'll say so.</p>
          </div>
        </li>
        <li>
          <span class="ty-next__num">3</span>
          <div>
            <strong>We go from there.</strong>
            <p>If we're a good fit I'll send a plain written scope before anything starts. No obligation, no pressure.</p>
          </div>
        </li>
      </ol>
    </div>

  </div>
</section>

{{-- ── WHILE YOU WAIT ────────────────────────────────── --}}
<section class="pf-section pf-section--alt" aria-labelledby="ty-browse-heading">
  <div class="container wide">
    <p class="eyebrow">While you wait</p>
    <h2 id="ty-browse-heading" class="display-title is-section">Take a look around.</h2>
    <p class="sec-intro" style="max-width:48ch;margin-bottom:2rem">A few places worth visiting while my reply is on the way.</p>

    <div class="ty-browse-grid">

      <a class="ty-browse-card" href="{{ home_url('/projects/') }}">
        <div class="ty-browse-card__icon">{!! \App\mh_svg_icon('globe', 22) !!}</div>
        <h3 class="ty-browse-card__title">Example sites</h3>
        <p class="ty-browse-card__body">WordPress projects for shops, inns, tours, and restaurants. A clear picture of what a finished build looks like.</p>
        <span class="ty-browse-card__link">Browse all projects →</span>
      </a>

      <a class="ty-browse-card" href="{{ home_url('/services/') }}">
        <div class="ty-browse-card__icon">{!! \App\mh_svg_icon('wordpress', 22) !!}</div>
        <h3 class="ty-browse-card__title">Services</h3>
        <p class="ty-browse-card__body">What I build, how the process works, who I typically work with, and what you get at handoff.</p>
        <span class="ty-browse-card__link">Read about services →</span>
      </a>

      <a class="ty-browse-card" href="{{ home_url('/uses/') }}">
        <div class="ty-browse-card__icon">{!! \App\mh_svg_icon('code', 22) !!}</div>
        <h3 class="ty-browse-card__title">Stack and tools</h3>
        <p class="ty-browse-card__body">The tools and frameworks I use on real projects — Sage, Tailwind, Cursor AI, GitHub Actions, HubSpot, and more.</p>
        <span class="ty-browse-card__link">See what I use →</span>
      </a>

      <a class="ty-browse-card" href="{{ get_permalink(get_option('page_for_posts')) ?: home_url('/blog/') }}">
        <div class="ty-browse-card__icon">{!! \App\mh_svg_icon('pen', 22) !!}</div>
        <h3 class="ty-browse-card__title">Journal</h3>
        <p class="ty-browse-card__body">WordPress tips, code snippets, and notes from real builds. Most posts have something copy-paste ready.</p>
        <span class="ty-browse-card__link">Read the journal →</span>
      </a>

      <a class="ty-browse-card" href="{{ home_url('/about/') }}">
        <div class="ty-browse-card__icon">{!! \App\mh_svg_icon('user', 22) !!}</div>
        <h3 class="ty-browse-card__title">About</h3>
        <p class="ty-browse-card__body">Background, resume, and what I'm focused on right now. Open to remote and on-site work.</p>
        <span class="ty-browse-card__link">About me →</span>
      </a>

      <a class="ty-browse-card" href="{{ home_url('/code/') }}">
        <div class="ty-browse-card__icon">{!! \App\mh_svg_icon('github', 22) !!}</div>
        <h3 class="ty-browse-card__title">Code and GitHub</h3>
        <p class="ty-browse-card__body">GitHub profile, featured repos, contribution history, and resume. The technical side of the work.</p>
        <span class="ty-browse-card__link">See the code →</span>
      </a>

    </div>
  </div>
</section>

{{-- ── RECENT PROJECTS ───────────────────────────────── --}}
@if (! empty($featured))
<section class="pf-section" aria-labelledby="ty-work-heading">
  <div class="container wide">
    <p class="eyebrow">Recent work</p>
    <h2 id="ty-work-heading" class="display-title is-section">A few recent projects.</h2>
    <div class="svc-work-grid">
      @foreach ($featured as $p)
        @php
          $href = esc_url($p['url'] ?? \App\mh_concept_page_url((string) ($p['slug'] ?? '')));
        @endphp
        <a class="svc-work-card" href="{{ $href }}">
          @if (! empty($p['image']))
            <div class="svc-work-card__img">
              <img src="{{ esc_url($p['image']) }}" alt="{{ esc_attr($p['title']) }} — WordPress project" width="480" height="270" loading="lazy" decoding="async">
            </div>
          @else
            <div class="svc-work-card__img svc-work-card__img--placeholder">{!! \App\mh_svg_icon('wordpress', 28) !!}</div>
          @endif
          <div class="svc-work-card__body">
            <span class="svc-work-card__cat">{{ $p['cat'] }}</span>
            <h3 class="svc-work-card__title">{{ $p['title'] }}</h3>
            <p class="svc-work-card__place">{!! \App\mh_svg_icon('map', 12) !!} {{ $p['place'] }}</p>
          </div>
        </a>
      @endforeach
    </div>
    <p style="margin-top:1.75rem;text-align:center">
      <a class="h-text-arrow" href="{{ home_url('/projects/') }}">Browse all {{ count(\App\mh_work_page_items()) }} projects →</a>
    </p>
  </div>
</section>
@endif

{{-- ── FOOTER NOTE ───────────────────────────────────── --}}
<div class="ty-footer-note container wide">
  <a href="{{ home_url('/') }}">← Back to home</a>
</div>

@include('partials.cta-band', [
  'kicker' => __('Still here?', 'sage'),
  'title' => __('Want to keep exploring?', 'sage'),
  'text' => __('Browse example sites, read the journal, or write if you already know what you need.', 'sage'),
  'label' => __('Say hello', 'sage'),
  'secondary' => __('Example sites', 'sage'),
  'secondaryHref' => home_url('/projects/'),
])

@endsection
