{{--
  Template Name: Contact
--}}
@extends('layouts.app')

@section('content')
@php
  $gh = \App\Github::fetchUser(\App\mh_github_login());
@endphp

{{-- HERO --}}
@component('partials.page-hero', ['extra' => 'contact-hero'])
  <p class="eyebrow">{{ \App\field('cnt_kicker', __('Contact', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('cnt_h1', __('Say hello.', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('cnt_lede', __('Want to contact a WordPress developer about a role, a build, or something you are stuck on? Send a few sentences. I read everything and reply within one business day (ET).', 'sage')) }}
  </p>
@endcomponent

@include('partials.page-nav', [
  'pills' => [
    ['write', __('Write', 'sage')],
    ['tips', __('What helps', 'sage')],
    ['next', __('What happens next', 'sage')],
  ],
])

{{-- FORM + ASIDE --}}
<section class="contact-main" id="write" aria-labelledby="contact-form-heading">
  <div class="container wide contact-split">

    <div class="contact-form-panel">
      <h2 id="contact-form-heading" class="display-title is-section">
        {{ \App\field('cnt_form_h2', __('Write a note.', 'sage')) }}
      </h2>
      <p class="sec-intro">
        {{ \App\field('cnt_form_intro', __('Name, email, and a few sentences are enough. No pitch deck required. This form goes straight to my inbox.', 'sage')) }}
      </p>
      @include('partials.booking-link')

      @include('partials.contact-form', [
        'compact' => false,
        'formId' => 'contact-form',
        'statusId' => 'contact-status',
        'actionUrl' => get_permalink(),
      ])
    </div>

    {{-- Aside --}}
    <div class="contact-aside-v2">

      {{-- Response time card --}}
      <div class="contact-info-card">
        <h2 class="contact-info-card__title">Before you write</h2>
        <ul class="contact-info-list">
          <li>
            <span class="contact-info-icon">{!! \App\mh_svg_icon('calendar', 16) !!}</span>
            <div>
              <strong>Reply time</strong>
              <p>{{ ucfirst(\App\mh_reply_sla('phrase')) }}. I read every note.</p>
            </div>
          </li>
          @if (\App\mh_is_hireable($gh))
          <li>
            <span class="contact-info-icon">@include('partials.avail-mark', ['gh' => $gh])</span>
            <div>
              <strong>{{ \App\mh_availability_label($gh, __('Open for work', 'sage')) }}</strong>
              <p>Full-time, part-time, freelance, or agency overflow.</p>
            </div>
          </li>
          @endif
          <li>
            <span class="contact-info-icon">{!! \App\mh_svg_icon('map', 16) !!}</span>
            <div>
              <strong>Location</strong>
              <p>Eastern Time. Available remotely anywhere.</p>
            </div>
          </li>
          <li>
            <span class="contact-info-icon">{!! \App\mh_svg_icon('code', 16) !!}</span>
            <div>
              <strong>Best fit</strong>
              <p>Full-stack web apps, WordPress platforms, plugins, integrations, and agency partnerships.</p>
            </div>
          </li>
        </ul>
      </div>

      {{-- Elsewhere --}}
      <div class="contact-info-card">
        <h2 class="contact-info-card__title">{{ \App\field('cnt_else_h2', __('Find me elsewhere', 'sage')) }}</h2>
        <p style="font-size:.9rem;color:var(--color-text-secondary);margin:0 0 1rem">{{ \App\field('cnt_aside', __('Prefer GitHub or LinkedIn? Those work too.', 'sage')) }}</p>
        @include('partials.social', ['labeled' => true, 'cards' => true, 'links' => \App\mh_contact_else_links()])
      </div>

    </div>

  </div>
</section>

{{-- WHAT TO SEND --}}
<section class="pf-section pf-section--alt" id="tips" aria-labelledby="contact-tips-heading">
  <div class="container wide">
    <div class="sec-head">
      <div>
        <p class="eyebrow">What to include</p>
        <h2 id="contact-tips-heading" class="display-title is-section">{{ \App\field('cnt_tips_h2', __('Three things that help.', 'sage')) }}</h2>
        <p class="sec-intro">{{ \App\field('cnt_tips_intro', __('You don\'t need a spec. These three make it easier for me to reply with something useful.', 'sage')) }}</p>
      </div>
    </div>
    <ul class="contact-tips">
      @foreach (\App\field_rows('cnt_tips', \App\mh_contact_tips()) as $tip)
        <li>
          <h3>{{ $tip['title'] ?? '' }}</h3>
          <p>{{ $tip['text'] ?? '' }}</p>
        </li>
      @endforeach
    </ul>
  </div>
</section>

{{-- WHAT HAPPENS NEXT --}}
<section class="pf-section" id="next" aria-labelledby="contact-expect-heading">
  <div class="container wide">
    <div class="sec-head">
      <div>
        <p class="eyebrow">After you send</p>
        <h2 id="contact-expect-heading" class="display-title is-section">{{ \App\field('cnt_expect_h2', __('What happens next.', 'sage')) }}</h2>
        <p class="sec-intro">{{ \App\field('cnt_expect_intro', __('A fair picture of how I use this inbox.', 'sage')) }}</p>
      </div>
    </div>
    <ul class="contact-expect">
      @foreach (\App\field_rows('cnt_expect', \App\mh_contact_expect()) as $item)
        <li>
          <h3>{{ $item['title'] ?? '' }}</h3>
          <p>{{ $item['text'] ?? '' }}</p>
        </li>
      @endforeach
    </ul>
  </div>
</section>

@include('partials.page-faq', [
  'id' => 'questions',
  'headingId' => 'contact-faq-heading',
  'alt' => true,
  'eyebrow' => __('Questions', 'sage'),
  'h2' => \App\field('cnt_faq_h2', __('Before you contact a WordPress developer.', 'sage')),
  'intro' => \App\field('cnt_faq_intro', __('Short answers about what to send and what happens next.', 'sage')),
  'items' => \App\mh_page_faq_rows('cnt_faq', \App\mh_contact_faq_defaults()),
  'foot' => \App\field_html('cnt_faq_foot', sprintf(
    /* translators: 1: Projects URL, 2: About URL, 3: Journal URL */
    __('Want background first? See the <a href="%1$s">projects</a>, read <a href="%2$s">about me</a>, or browse the <a href="%3$s">journal</a>.', 'sage'),
    esc_url(\App\mh_work_listing_url()),
    esc_url(home_url('/about/')),
    esc_url(get_permalink((int) get_option('page_for_posts')) ?: home_url('/blog/'))
  )),
])

@endsection
