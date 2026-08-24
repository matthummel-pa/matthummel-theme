{{--
  Template Name: Contact
--}}
@extends('layouts.app')

@section('content')
@php
  $mhStatus = isset($_GET['contact']) ? sanitize_key(wp_unslash($_GET['contact'])) : '';
  $mhError  = $mhStatus === 'error';
  $mhOk     = $mhStatus === 'success';
  $oldName    = \App\mh_contact_old('name');
  $oldEmail   = \App\mh_contact_old('email');
  $oldWho     = \App\mh_contact_prefill('who');
  $oldSubject = \App\mh_contact_prefill('subject');
  $oldMessage = \App\mh_contact_prefill('message');
  $invalid    = \App\mh_contact_old_errors();
@endphp

{{-- HERO --}}
@component('partials.page-hero', ['extra' => 'contact-hero'])
  <p class="eyebrow">{{ \App\field('cnt_kicker', __('Contact', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('cnt_h1', __('Say hello.', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('cnt_lede', __('Questions about a post, a code snippet, or GitHub are welcome. So is a note about a WordPress site — for a Gettysburg business or anywhere else. I read everything and reply within one or two business days.', 'sage')) }}
  </p>
  <div class="contact-hero-signals">
    <span class="contact-signal">
      <span class="h-badge__dot" aria-hidden="true"></span>
      Open for new work
    </span>
    <span class="contact-signal">
      {!! \App\mh_svg_icon('calendar', 14) !!}
      Replies within 1–2 business days
    </span>
    <span class="contact-signal">
      {!! \App\mh_svg_icon('map', 14) !!}
      Gettysburg, PA (EST)
    </span>
  </div>
@endcomponent

{{-- FORM + ASIDE --}}
<section class="contact-main" aria-labelledby="contact-form-heading">
  <div class="container wide contact-split">

    <div class="contact-form-panel">
      <h2 id="contact-form-heading" class="display-title is-section">
        {{ \App\field('cnt_form_h2', __('Write a note.', 'sage')) }}
      </h2>
      <p class="sec-intro">
        {{ \App\field('cnt_form_intro', __('Name, email, and a few sentences are enough. No pitch deck required. This form goes straight to my inbox.', 'sage')) }}
      </p>

      @if ($mhOk)
        <p class="form-success" id="contact-status" role="status" tabindex="-1">
          {!! \App\mh_svg_icon('check', 18) !!}
          {{ \App\field('cnt_success', __('Thanks — I got it and will write back soon.', 'sage')) }}
        </p>
      @elseif ($mhError)
        <p class="form-error" id="contact-status" role="alert" tabindex="-1">
          {{ \App\field('cnt_error', __('Something went wrong. Check the required fields and try again.', 'sage')) }}
        </p>
      @endif

      <form class="contact-form{{ $mhError ? ' is-error' : '' }}" id="contact-form" method="post" action="{{ esc_url(get_permalink()) }}" novalidate>
        @php(wp_nonce_field('mh_contact', 'mh_contact_nonce'))
        <input type="hidden" name="action" value="mh_contact">
        <p class="hp visually-hidden">
          <label for="cf-company">Company (leave empty)</label>
          <input id="cf-company" type="text" name="mh_hp" value="" tabindex="-1" autocomplete="off">
        </p>

        <div class="contact-form__row">
          <div class="field">
            <label for="cf-name">Name <span class="field-req" aria-hidden="true">*</span></label>
            <input id="cf-name" type="text" name="mh_name" autocomplete="name" required aria-required="true" placeholder="Your name" value="{{ $oldName }}"@if (in_array('name', $invalid, true)) aria-invalid="true" aria-describedby="contact-status"@endif>
          </div>
          <div class="field">
            <label for="cf-email">Email <span class="field-req" aria-hidden="true">*</span></label>
            <input id="cf-email" type="email" name="mh_email" autocomplete="email" inputmode="email" required aria-required="true" placeholder="you@example.com" aria-describedby="cf-email-hint{{ in_array('email', $invalid, true) ? ' contact-status' : '' }}" value="{{ $oldEmail }}"@if (in_array('email', $invalid, true)) aria-invalid="true"@endif>
            <p class="field-hint" id="cf-email-hint">I only use this to reply. No newsletter.</p>
          </div>
        </div>

        <div class="field">
          <label for="cf-who">{{ \App\field('cnt_who_label', __('Who you are', 'sage')) }} <span class="field-opt">(optional — helps me reply in the right shape)</span></label>
          <select id="cf-who" name="mh_who" autocomplete="off">
            <option value=""@if ($oldWho === '') selected @endif>Choose one</option>
            <option value="developer"@if ($oldWho === 'developer') selected @endif>A developer</option>
            <option value="recruiter"@if ($oldWho === 'recruiter') selected @endif>A recruiter or hiring manager</option>
            <option value="business"@if ($oldWho === 'business') selected @endif>A shop or small business</option>
            <option value="agency"@if ($oldWho === 'agency') selected @endif>A marketing or design agency</option>
            <option value="learning"@if ($oldWho === 'learning') selected @endif>Someone learning web development</option>
            <option value="other"@if ($oldWho === 'other') selected @endif>Something else</option>
          </select>
        </div>

        <div class="field">
          <label for="cf-subject">Subject <span class="field-opt">(optional)</span></label>
          <input id="cf-subject" type="text" name="mh_subject" autocomplete="off" placeholder="e.g. WordPress site for a local shop" value="{{ $oldSubject }}">
        </div>

        <div class="field">
          <label for="cf-message">Message <span class="field-req" aria-hidden="true">*</span></label>
          <textarea id="cf-message" name="mh_message" rows="7" required aria-required="true" placeholder="Tell me what you're working on or what you need. A few sentences is plenty." aria-describedby="cf-message-hint{{ in_array('message', $invalid, true) ? ' contact-status' : '' }}"@if (in_array('message', $invalid, true)) aria-invalid="true"@endif>{{ $oldMessage }}</textarea>
          <p class="field-hint" id="cf-message-hint">{{ \App\field('cnt_message_hint', __('No pitch deck needed. Paste a URL if you have one.', 'sage')) }}</p>
        </div>

        <div class="contact-form__actions">
          <button class="btn" type="submit">
            {!! \App\mh_svg_icon('mail', 16) !!}
            {{ \App\field('cnt_submit', __('Send note', 'sage')) }}
          </button>
          <p class="field-hint">{{ \App\field('cnt_reply_note', __('I usually reply within one or two business days (EST).', 'sage')) }}</p>
        </div>
      </form>
    </div>

    {{-- Aside --}}
    <aside class="contact-aside-v2">

      {{-- Response time card --}}
      <div class="contact-info-card">
        <h2 class="contact-info-card__title">Before you write</h2>
        <ul class="contact-info-list">
          <li>
            <span class="contact-info-icon">{!! \App\mh_svg_icon('calendar', 16) !!}</span>
            <div>
              <strong>Reply time</strong>
              <p>Usually one or two business days. I read every note.</p>
            </div>
          </li>
          <li>
            <span class="contact-info-icon">{!! \App\mh_svg_icon('briefcase', 16) !!}</span>
            <div>
              <strong>Open for work</strong>
              <p>Full-time, part-time, freelance, or agency overflow.</p>
            </div>
          </li>
          <li>
            <span class="contact-info-icon">{!! \App\mh_svg_icon('map', 16) !!}</span>
            <div>
              <strong>Location</strong>
              <p>Gettysburg, PA (EST). Available remotely anywhere.</p>
            </div>
          </li>
          <li>
            <span class="contact-info-icon">{!! \App\mh_svg_icon('code', 16) !!}</span>
            <div>
              <strong>Best fit</strong>
              <p>WordPress sites, plugins, PHP, and web apps.</p>
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

    </aside>

  </div>
</section>

{{-- WHAT TO SEND --}}
<section class="pf-section pf-section--alt" aria-labelledby="contact-tips-heading">
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
<section class="pf-section" aria-labelledby="contact-expect-heading">
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

@endsection
