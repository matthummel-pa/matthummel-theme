{{--
  Template Name: Start (project brief)
--}}
@extends('layouts.app')

@section('content')
@php
  $mhStatus = isset($_GET['start']) ? sanitize_key(wp_unslash($_GET['start'])) : '';
  $mhError  = $mhStatus === 'error';
  $invalid  = \App\mh_discovery_old_errors();
  $v = static fn (string $key, string $default = '') => \App\mh_discovery_old($key, $default);

  $projectTypes = [
    'new-site'   => __('New WordPress site', 'sage'),
    'rebuild'    => __('Rebuild or redesign', 'sage'),
    'plugin'     => __('Plugin or custom feature', 'sage'),
    'overflow'   => __('Agency overflow / white-label', 'sage'),
    'other'      => __('Something else', 'sage'),
  ];
  $roles = [
    'agency-pm'    => __('Agency project manager', 'sage'),
    'agency-owner' => __('Agency owner', 'sage'),
    'designer'     => __('Designer', 'sage'),
    'developer'    => __('Developer', 'sage'),
    'shop-owner'   => __('Shop or business owner', 'sage'),
    'other'        => __('Something else', 'sage'),
  ];
  $timelines = [
    'asap'     => __('ASAP', 'sage'),
    '2-4w'     => __('2–4 weeks', 'sage'),
    '1-2m'     => __('1–2 months', 'sage'),
    'flexible' => __('Flexible', 'sage'),
    'unsure'   => __('Not sure yet', 'sage'),
  ];
@endphp

@component('partials.page-hero', ['extra' => 'start-hero', 'split' => true, 'asideLabel' => __('Brief snapshot', 'sage')])
  <p class="eyebrow">{{ \App\field('start_kicker', __('Project brief', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('start_h1', __('Prepare for our first meeting.', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('start_lede', __('Four short steps. The answers agencies and shops usually cover in discovery. I read every brief before we talk so the first call is useful, not a blank page.', 'sage')) }}
  </p>
  <p class="start-hero-alt">
    {{ __('Prefer a quick note instead?', 'sage') }}
    <a href="{{ home_url('/contact/') }}">{{ __('Say hello on the contact form', 'sage') }} <span aria-hidden="true">→</span></a>
  </p>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/start',
      'icon' => 'briefcase',
      'title' => __('Discovery brief', 'sage'),
      'meta' => __('Four steps · one form', 'sage'),
      'stats' => [
        ['value' => '4', 'label' => __('Steps', 'sage')],
        ['value' => __('Written', 'sage'), 'label' => __('Scope before build', 'sage')],
        ['value' => '1–2', 'label' => __('Business days to reply', 'sage')],
        ['value' => __('Remote', 'sage'), 'label' => __('Or on-site', 'sage')],
      ],
      'link' => [
        'label' => __('Contact form instead', 'sage'),
        'href' => home_url('/contact/'),
      ],
    ])
  @endslot
@endcomponent

<section class="start-main" aria-labelledby="start-form-heading">
  <div class="container narrow">

    <h2 id="start-form-heading" class="visually-hidden">{{ __('Project discovery brief', 'sage') }}</h2>

    @if ($mhError)
      <p class="form-error" id="start-status" role="alert" tabindex="-1">
        {{ \App\field('start_error', __('Something went wrong. Check the required fields and try again.', 'sage')) }}
      </p>
    @endif

    <form
      class="discovery-form contact-form{{ $mhError ? ' is-error' : '' }}"
      id="discovery-form"
      method="post"
      action="{{ esc_url(get_permalink()) }}"
      novalidate
      data-discovery-form
    >
      @php(wp_nonce_field('mh_discovery', 'mh_discovery_nonce'))
      <input type="hidden" name="action" value="mh_discovery">
      <p class="hp visually-hidden">
        <label for="df-company-hp">Company (leave empty)</label>
        <input id="df-company-hp" type="text" name="mh_hp" value="" tabindex="-1" autocomplete="off">
      </p>

      {{-- Progress --}}
      <ol class="discovery-progress" aria-label="{{ __('Brief steps', 'sage') }}" data-discovery-progress>
        <li class="is-active" data-step-indicator="1"><span class="discovery-progress__num">1</span> {{ __('You', 'sage') }}</li>
        <li data-step-indicator="2"><span class="discovery-progress__num">2</span> {{ __('Project', 'sage') }}</li>
        <li data-step-indicator="3"><span class="discovery-progress__num">3</span> {{ __('Goals', 'sage') }}</li>
        <li data-step-indicator="4"><span class="discovery-progress__num">4</span> {{ __('Send', 'sage') }}</li>
      </ol>

      {{-- Step 1: You --}}
      <fieldset class="discovery-step is-active" data-discovery-step="1" aria-labelledby="df-step1-legend">
        <legend id="df-step1-legend" class="discovery-step__legend">
          <span class="discovery-step__eyebrow">Step 1 of 4</span>
          About you
        </legend>
        <p class="discovery-step__intro">Who I should reply to, and how you fit the project.</p>

        <div class="contact-form__row">
          <div class="field">
            <label for="df-name">Name <span class="field-req" aria-hidden="true">*</span></label>
            <input id="df-name" type="text" name="mh_name" autocomplete="name" required aria-required="true" placeholder="Your name" value="{{ $v('name') }}"@if (in_array('name', $invalid, true)) aria-invalid="true" aria-describedby="start-status"@endif>
          </div>
          <div class="field">
            <label for="df-email">Email <span class="field-req" aria-hidden="true">*</span></label>
            <input id="df-email" type="email" name="mh_email" autocomplete="email" inputmode="email" required aria-required="true" placeholder="you@agency.com" value="{{ $v('email') }}"@if (in_array('email', $invalid, true)) aria-invalid="true" aria-describedby="start-status"@endif>
          </div>
        </div>

        <div class="contact-form__row">
          <div class="field">
            <label for="df-company">Company or agency <span class="field-opt">(optional)</span></label>
            <input id="df-company" type="text" name="mh_company" autocomplete="organization" placeholder="Agency or shop name" value="{{ $v('company') }}">
          </div>
          <div class="field">
            <label for="df-role">Your role <span class="field-opt">(optional)</span></label>
            <select id="df-role" name="mh_role" autocomplete="organization-title">
              <option value="">Choose one</option>
              @foreach ($roles as $key => $label)
                <option value="{{ $key }}"@if ($v('role') === $key) selected @endif>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </fieldset>

      {{-- Step 2: Project --}}
      <fieldset class="discovery-step" data-discovery-step="2" aria-labelledby="df-step2-legend">
        <legend id="df-step2-legend" class="discovery-step__legend">
          <span class="discovery-step__eyebrow">Step 2 of 4</span>
          The project
        </legend>
        <p class="discovery-step__intro">What kind of build this is, and what needs to happen.</p>

        <div class="field">
          <label for="df-type">Project type <span class="field-req" aria-hidden="true">*</span></label>
          <select id="df-type" name="mh_project_type" required aria-required="true"@if (in_array('project_type', $invalid, true)) aria-invalid="true" aria-describedby="start-status"@endif>
            <option value="">Choose one</option>
            @foreach ($projectTypes as $key => $label)
              <option value="{{ $key }}"@if ($v('project_type') === $key) selected @endif>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div class="contact-form__row">
          <div class="field">
            <label for="df-client">End client or shop <span class="field-opt">(optional)</span></label>
            <input id="df-client" type="text" name="mh_client" autocomplete="off" placeholder="Who the site is for" value="{{ $v('client') }}">
          </div>
          <div class="field">
            <label for="df-url">Current site URL <span class="field-opt">(optional)</span></label>
            <input id="df-url" type="url" name="mh_url" autocomplete="url" inputmode="url" placeholder="https://" value="{{ $v('url') }}">
          </div>
        </div>

        <div class="field">
          <label for="df-need">What needs building or fixing? <span class="field-req" aria-hidden="true">*</span></label>
          <textarea id="df-need" name="mh_need" rows="5" required aria-required="true" placeholder="A few sentences: what’s broken today, what you want instead, or what “done” looks like for this phase."@if (in_array('need', $invalid, true)) aria-invalid="true" aria-describedby="start-status"@endif>{{ $v('need') }}</textarea>
          <p class="field-hint">No wireframe required. Paste links if you have them.</p>
        </div>
      </fieldset>

      {{-- Step 3: Goals --}}
      <fieldset class="discovery-step" data-discovery-step="3" aria-labelledby="df-step3-legend">
        <legend id="df-step3-legend" class="discovery-step__legend">
          <span class="discovery-step__eyebrow">Step 3 of 4</span>
          Goals and constraints
        </legend>
        <p class="discovery-step__intro">What success looks like, and what I should know before we scope.</p>

        <div class="field">
          <label for="df-success">What does a win look like? <span class="field-req" aria-hidden="true">*</span></label>
          <textarea id="df-success" name="mh_success" rows="4" required aria-required="true" placeholder="e.g. Shop staff can edit pages without me. Agency can hand the site back cleanly. Launch before the busy season."@if (in_array('success', $invalid, true)) aria-invalid="true" aria-describedby="start-status"@endif>{{ $v('success') }}</textarea>
        </div>

        <div class="field">
          <label for="df-audience">Who visits the site? <span class="field-opt">(optional)</span></label>
          <input id="df-audience" type="text" name="mh_audience" autocomplete="off" placeholder="Locals, tourists, B2B buyers…" value="{{ $v('audience') }}">
        </div>

        <div class="contact-form__row">
          <div class="field">
            <label for="df-timeline">Ideal timeline <span class="field-opt">(optional)</span></label>
            <select id="df-timeline" name="mh_timeline">
              <option value="">Choose one</option>
              @foreach ($timelines as $key => $label)
                <option value="{{ $key }}"@if ($v('timeline') === $key) selected @endif>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="df-editors">Who edits after handoff? <span class="field-opt">(optional)</span></label>
            <input id="df-editors" type="text" name="mh_editors" autocomplete="off" placeholder="Shop owner, agency team, me…" value="{{ $v('editors') }}">
          </div>
        </div>

        <div class="field">
          <label for="df-stack">Hosting, stack, or must-haves <span class="field-opt">(optional)</span></label>
          <textarea id="df-stack" name="mh_stack" rows="3" placeholder="Existing host, must keep WordPress, need forms, bookings, multilingual…">{{ $v('stack') }}</textarea>
        </div>
      </fieldset>

      {{-- Step 4: Send --}}
      <fieldset class="discovery-step" data-discovery-step="4" aria-labelledby="df-step4-legend">
        <legend id="df-step4-legend" class="discovery-step__legend">
          <span class="discovery-step__eyebrow">Step 4 of 4</span>
          Anything else?
        </legend>
        <p class="discovery-step__intro">Optional notes, then send. I’ll reply within one or two business days.</p>

        <div class="field">
          <label for="df-notes">Other notes <span class="field-opt">(optional)</span></label>
          <textarea id="df-notes" name="mh_notes" rows="4" placeholder="NDA needed? Preferred kickoff week? Context I should read before we talk.">{{ $v('notes') }}</textarea>
        </div>

        <div class="discovery-review" data-discovery-review hidden>
          <p class="discovery-review__label">Quick check</p>
          <dl class="discovery-review__list" data-discovery-review-list></dl>
        </div>

        <div class="contact-form__actions">
          <button class="btn" type="submit" data-discovery-submit>
            {!! \App\mh_svg_icon('mail', 16) !!}
            {{ \App\field('start_submit', __('Send brief', 'sage')) }}
          </button>
          <p class="field-hint">{{ \App\field('start_reply_note', __('I usually reply within one or two business days (EST).', 'sage')) }}</p>
        </div>
      </fieldset>

      <div class="discovery-nav" data-discovery-nav hidden>
        <button type="button" class="btn btn-outline" data-discovery-back hidden>{{ __('Back', 'sage') }}</button>
        <button type="button" class="btn" data-discovery-next>{{ __('Continue', 'sage') }}</button>
      </div>
    </form>

    <p class="start-footnote">
      Prefer email?
      <a href="{{ home_url('/contact/') }}">Use the short contact form</a>
      instead. Same inbox.
    </p>
  </div>
</section>

@include('partials.cta-band', [
  'kicker' => __('Prefer a short note?', 'sage'),
  'title' => __('Skip the brief.', 'sage'),
  'text' => __('The contact form is fine if you only need a quick question answered. Same inbox either way.', 'sage'),
  'label' => __('Contact form', 'sage'),
  'secondary' => __('Hire me', 'sage'),
])
@endsection
