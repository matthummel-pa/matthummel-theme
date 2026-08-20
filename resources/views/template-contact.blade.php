{{--
  Template Name: Contact
--}}
@extends('layouts.app')

@section('content')
  @php
    $mhStatus = isset($_GET['contact']) ? sanitize_key($_GET['contact']) : '';
    $mhError = $mhStatus === 'error';
    $mhOk = $mhStatus === 'success';
    $oldName = \App\mh_contact_old('name');
    $oldEmail = \App\mh_contact_old('email');
    $oldWho = \App\mh_contact_prefill('who');
    $oldSubject = \App\mh_contact_prefill('subject');
    $oldMessage = \App\mh_contact_prefill('message');
    $invalid = \App\mh_contact_old_errors();
  @endphp

  @component('partials.page-hero', ['extra' => 'contact-hero'])
    <p class="eyebrow">{{ \App\field('cnt_kicker', __('Contact', 'sage')) }}</p>
    <h1 class="display-title is-hero">{{ \App\field('cnt_h1', __('Say hello.', 'sage')) }}</h1>
    <p class="lead">{{ \App\field('cnt_lede', __('Questions about a post, a snippet, or GitHub are welcome. So is a note about a WordPress site, a plugin, or another web app. I usually reply in one or two business days.', 'sage')) }}</p>
  @endcomponent

  <section class="contact-main" aria-labelledby="contact-form-heading">
    <div class="container wide contact-split">
      <div class="contact-form-panel">
        <h2 id="contact-form-heading" class="display-title is-section">{{ \App\field('cnt_form_h2', __('Write a note', 'sage')) }}</h2>
        <p class="sec-intro">{{ \App\field('cnt_form_intro', __('Name, email, and a few sentences are enough. I read every note. This form is the reliable inbox.', 'sage')) }}</p>

        @if ($mhOk)
          <p class="form-success" id="contact-status" role="status" tabindex="-1">{{ \App\field('cnt_success', __('Thanks. I got it and will write back soon.', 'sage')) }}</p>
        @elseif ($mhError)
          <p class="form-error" id="contact-status" role="alert" tabindex="-1">{{ \App\field('cnt_error', __('Something went wrong. Check the required fields and try again.', 'sage')) }}</p>
        @endif

        <form class="contact-form{{ $mhError ? ' is-error' : '' }}" id="contact-form" method="post" action="{{ esc_url(get_permalink()) }}">
          @php(wp_nonce_field('mh_contact', 'mh_contact_nonce'))
          <input type="hidden" name="action" value="mh_contact">
          <p class="hp visually-hidden">
            <label for="cf-company">{{ __('Company (leave empty)', 'sage') }}</label>
            <input id="cf-company" type="text" name="mh_hp" value="" tabindex="-1" autocomplete="off">
          </p>

          <div class="contact-form__row">
            <div class="field">
              <label for="cf-name">{{ __('Name', 'sage') }} <span class="field-req" aria-hidden="true">*</span><span class="visually-hidden"> {{ __('required', 'sage') }}</span></label>
              <input id="cf-name" type="text" name="mh_name" autocomplete="name" required aria-required="true" value="{{ $oldName }}"@if (in_array('name', $invalid, true)) aria-invalid="true" aria-describedby="contact-status"@endif>
            </div>
            <div class="field">
              <label for="cf-email">{{ __('Email', 'sage') }} <span class="field-req" aria-hidden="true">*</span><span class="visually-hidden"> {{ __('required', 'sage') }}</span></label>
              <input id="cf-email" type="email" name="mh_email" autocomplete="email" inputmode="email" required aria-required="true" aria-describedby="cf-email-hint{{ in_array('email', $invalid, true) ? ' contact-status' : '' }}" value="{{ $oldEmail }}"@if (in_array('email', $invalid, true)) aria-invalid="true"@endif>
              <p class="field-hint" id="cf-email-hint">{{ __('I only use this to reply. No list. No newsletter.', 'sage') }}</p>
            </div>
          </div>

          <div class="field">
            <label for="cf-who">{{ \App\field('cnt_who_label', __('Who you are', 'sage')) }} <span class="field-opt">{{ __('(optional)', 'sage') }}</span></label>
            <select id="cf-who" name="mh_who" autocomplete="off">
              <option value=""@if ($oldWho === '') selected @endif>{{ __('Choose one', 'sage') }}</option>
              <option value="developer"@if ($oldWho === 'developer') selected @endif>{{ __('A developer', 'sage') }}</option>
              <option value="learning"@if ($oldWho === 'learning') selected @endif>{{ __('Learning the web', 'sage') }}</option>
              <option value="business"@if ($oldWho === 'business') selected @endif>{{ __('A shop or team', 'sage') }}</option>
              <option value="agency"@if ($oldWho === 'agency') selected @endif>{{ __('A marketing agency', 'sage') }}</option>
              <option value="other"@if ($oldWho === 'other') selected @endif>{{ __('Something else', 'sage') }}</option>
            </select>
          </div>

          <div class="field">
            <label for="cf-subject">{{ __('Subject', 'sage') }} <span class="field-opt">{{ __('(optional)', 'sage') }}</span></label>
            <input id="cf-subject" type="text" name="mh_subject" autocomplete="off" value="{{ $oldSubject }}">
          </div>

          <div class="field">
            <label for="cf-message">{{ __('Message', 'sage') }} <span class="field-req" aria-hidden="true">*</span><span class="visually-hidden"> {{ __('required', 'sage') }}</span></label>
            <textarea id="cf-message" name="mh_message" rows="8" required aria-required="true" aria-describedby="cf-message-hint{{ in_array('message', $invalid, true) ? ' contact-status' : '' }}"@if (in_array('message', $invalid, true)) aria-invalid="true"@endif>{{ $oldMessage }}</textarea>
            <p class="field-hint" id="cf-message-hint">{{ \App\field('cnt_message_hint', __('A few sentences is enough. Paste a URL if you have one. No need for a long brief.', 'sage')) }}</p>
          </div>

          <div class="contact-form__actions">
            <button class="btn" type="submit">{{ \App\field('cnt_submit', __('Send hello', 'sage')) }}</button>
            <p class="field-hint">{{ \App\field('cnt_reply_note', __('I usually reply in one or two business days (Eastern Time).', 'sage')) }}</p>
          </div>
        </form>
      </div>

      <aside class="contact-aside" aria-labelledby="elsewhere-heading">
        <h2 id="elsewhere-heading" class="display-title is-section">{{ \App\field('cnt_else_h2', __('Find me elsewhere', 'sage')) }}</h2>
        <p>{{ \App\field('cnt_aside', __('Prefer GitHub or LinkedIn? Those work too. Ridges & Valleys is the Gettysburg studio site.', 'sage')) }}</p>
        @include('partials.social', ['labeled' => true, 'cards' => true, 'links' => \App\mh_contact_else_links()])
      </aside>
    </div>
  </section>

  <section class="pf-section pf-section--alt" aria-labelledby="contact-tips-heading">
    <div class="container wide">
      <header class="sec-head">
        <div>
          <p class="eyebrow">{{ \App\field('cnt_tips_kicker', __('A useful note', 'sage')) }}</p>
          <h2 id="contact-tips-heading" class="display-title is-section">{{ \App\field('cnt_tips_h2', __('What to send', 'sage')) }}</h2>
          <p class="sec-intro">{{ \App\field('cnt_tips_intro', __('You do not need a pitch deck. These three things help me reply in the right shape.', 'sage')) }}</p>
        </div>
      </header>
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

  <section class="pf-section" aria-labelledby="contact-expect-heading">
    <div class="container wide">
      <header class="sec-head">
        <div>
          <p class="eyebrow">{{ \App\field('cnt_expect_kicker', __('After you hit send', 'sage')) }}</p>
          <h2 id="contact-expect-heading" class="display-title is-section">{{ \App\field('cnt_expect_h2', __('What happens next', 'sage')) }}</h2>
          <p class="sec-intro">{{ \App\field('cnt_expect_intro', __('A fair picture of how I use this inbox.', 'sage')) }}</p>
        </div>
      </header>
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
