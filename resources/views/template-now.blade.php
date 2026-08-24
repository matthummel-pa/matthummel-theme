{{--
  Template Name: Now
--}}
@extends('layouts.app')

@php
  $gh      = \App\Github::fetchUser(\App\mh_github_login());
  $ghUrl   = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $ghBlog  = \App\mh_github_blog_url($gh);
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
@endphp

@section('content')

{{-- HERO --}}
@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('now_kicker', __('Now', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('now_h1', __('What I\'m doing right now.', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('now_lede', __('A snapshot of where my time and attention are going. Inspired by nownownow.com — a simple page that answers "what are you up to?" Updated August 2026.', 'sage')) }}
  </p>
  <p class="about-hero-links" style="margin-top:1rem">
    <a href="{{ home_url('/about/') }}">{!! \App\mh_svg_icon('user', 14) !!} Full background</a>
    <a href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 14) !!} Say hello</a>
    <a href="{{ esc_url($ghBlog) }}" rel="noopener" target="_blank">{!! \App\mh_svg_icon('globe', 14) !!} Studio</a>
  </p>
@endcomponent

{{-- CURRENT FOCUS --}}
<section class="pf-section" aria-labelledby="now-work-heading">
  <div class="container wide now-layout">

    <div class="now-main">

      <div class="now-block">
        <div class="now-block__head">
          <span class="now-block__icon">{!! \App\mh_svg_icon('briefcase', 20) !!}</span>
          <h2 id="now-work-heading" class="now-block__title">Building Ridges &amp; Valleys</h2>
        </div>
        <p>{{ \App\field('now_studio_p1', __('I recently started Ridges & Valleys, a WordPress studio focused on shops, tours, and inns in Gettysburg and Adams County, PA. I\'m building out concept sites for local business types — not a case-study deck, but real working demonstrations of what a WordPress site can look like for a specific kind of business.', 'sage')) }}</p>
        <p>{{ \App\field('now_studio_p2', __('It\'s early. The studio is new, the portfolio is growing, and I\'m still figuring out the right shape for local studio work. If you run a Gettysburg-area business and want to see what a clear, editable WordPress site looks like for your type of shop, that\'s exactly what I\'m building for.', 'sage')) }}</p>
        <a class="about-text-link" href="{{ esc_url($ghBlog) }}" rel="noopener" target="_blank">Visit ridgesandvalleys.com →</a>
      </div>

      <div class="now-block">
        <div class="now-block__head">
          <span class="now-block__icon">{!! \App\mh_svg_icon('code', 20) !!}</span>
          <h2 class="now-block__title">Open for work</h2>
        </div>
        <p>{{ \App\field('now_work_p1', __('Alongside the studio I\'m actively looking for new work. Full-time roles, contract arrangements, freelance projects, and agency overflow are all on the table. WordPress and PHP is the core, but I also do React, web apps, and Power Platform when it fits.', 'sage')) }}</p>
        <p>{{ \App\field('now_work_p2', __('If you\'re a recruiter, a hiring manager, or an agency that needs a WordPress developer — I\'m glad to hear from you. The fastest way to start is a short note.', 'sage')) }}</p>
        <a class="btn" href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 16) !!} Say hello</a>
      </div>

      <div class="now-block">
        <div class="now-block__head">
          <span class="now-block__icon">{!! \App\mh_svg_icon('pen', 20) !!}</span>
          <h2 class="now-block__title">Writing and sharing</h2>
        </div>
        <p>{{ \App\field('now_write_p1', __('I write short posts on WordPress, PHP, and the tools I use. Most include code you can paste into a theme or plugin. Posts go on this journal first, then sometimes cross-posted to DEV.to.', 'sage')) }}</p>
        <a class="about-text-link" href="{{ $writing }}">Read the journal →</a>
      </div>

      <div class="now-block">
        <div class="now-block__head">
          <span class="now-block__icon">{!! \App\mh_svg_icon('map', 20) !!}</span>
          <h2 class="now-block__title">Life in Gettysburg</h2>
        </div>
        <p>{{ \App\field('now_life_p1', __('I live in Gettysburg, Pennsylvania with my family. Nights and weekends are for kids, not keyboards, so I keep extra projects small and well-scoped. I work EST hours.', 'sage')) }}</p>
      </div>

      <div class="now-block now-block--current-list">
        <div class="now-block__head">
          <span class="now-block__icon">{!! \App\mh_svg_icon('book-open', 20) !!}</span>
          <h2 class="now-block__title">Right now — the short list</h2>
        </div>
        <ol class="now-list">
          @foreach (\App\field_lines('now_items', [
            __('Building WordPress concept sites for Gettysburg businesses at Ridges & Valleys.', 'sage'),
            __('Actively looking for full-time, contract, and freelance WordPress work.', 'sage'),
            __('Writing short posts on WordPress development — with code snippets.', 'sage'),
            __('Keeping extra projects small — family time is non-negotiable.', 'sage'),
            __('Using Cursor AI, Claude, and ChatGPT to build faster, reviewing every line before it ships.', 'sage'),
          ]) as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ol>
      </div>

    </div>

    {{-- Sidebar --}}
    <aside class="now-sidebar">
      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">Last updated</p>
        <p class="now-sidebar-card__value">August 2026</p>
      </div>
      <div class="now-sidebar-card now-sidebar-card--avail">
        <p class="now-sidebar-card__label">
          <span class="h-badge__dot" aria-hidden="true"></span>
          Status
        </p>
        <p class="now-sidebar-card__value">Open for work</p>
        <ul class="now-sidebar-card__list">
          <li>✓ Full-time roles</li>
          <li>✓ Contract / freelance</li>
          <li>✓ Agency overflow</li>
          <li>✓ Remote anywhere</li>
        </ul>
        <a class="btn" href="{{ home_url('/contact/') }}" style="width:100%;justify-content:center;margin-top:.5rem">
          {!! \App\mh_svg_icon('mail', 15) !!} Start a conversation
        </a>
      </div>
      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">Location</p>
        <p class="now-sidebar-card__value">Gettysburg, PA</p>
        <p class="now-sidebar-card__sub">Eastern Time (EST)</p>
      </div>
      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">Primary stack</p>
        <p class="now-sidebar-card__value">WordPress + PHP</p>
        <p class="now-sidebar-card__sub">Also React, Power Platform</p>
      </div>
    </aside>

  </div>
</section>

{{-- CTA --}}
<section class="cta-band" aria-labelledby="now-cta-heading">
  <div class="container wide cta-band-inner">
    <div>
      <p class="eyebrow eyebrow--on-dark">Get in touch</p>
      <h2 id="now-cta-heading" class="display-title is-section">Something I can help with?</h2>
      <p>A short note is enough to start. I usually reply within a day or two.</p>
    </div>
    <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!} Say hello
    </a>
  </div>
</section>

@endsection
