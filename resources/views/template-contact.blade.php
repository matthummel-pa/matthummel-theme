{{--
  Template Name: Contact
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container wide">
    <p class="eyebrow">{{ \App\field('cnt_kicker', __('Contact', 'sage')) }}</p>
    <h1 class="display-title is-hero">{{ \App\field('cnt_h1', __('Say hello.', 'sage')) }}</h1>
    <p class="lead">{{ \App\field('cnt_lede', __('Questions about a post, a snippet, or GitHub are welcome. So is a note about a WordPress site, a plugin, or another web app. I usually reply in one or two business days.', 'sage')) }}</p>
  </header>

  <div class="contact-wrap contact-split">
    <div>
      @php($mhStatus = isset($_GET['contact']) ? sanitize_key($_GET['contact']) : '')
      @if ($mhStatus === 'success')
        <p class="form-success" role="status">{{ \App\field('cnt_success', __('Thanks. I got it and will write back soon.', 'sage')) }}</p>
      @elseif ($mhStatus === 'error')
        <p class="form-error" role="alert">{{ \App\field('cnt_error', __('Something went wrong. Check the fields and try again.', 'sage')) }}</p>
      @endif

      <form class="contact-form" method="post" action="">
        @php(wp_nonce_field('mh_contact', 'mh_contact_nonce'))
        <input type="hidden" name="action" value="mh_contact">
        <p class="hp"><label>{{ __('Leave this field empty', 'matthummel') }}<input type="text" name="mh_hp" tabindex="-1" autocomplete="off"></label></p>

        <div class="field">
          <label for="cf-name">{{ __('Name', 'matthummel') }}</label>
          <input id="cf-name" type="text" name="mh_name" autocomplete="name" required>
        </div>
        <div class="field">
          <label for="cf-email">{{ __('Email', 'matthummel') }}</label>
          <input id="cf-email" type="email" name="mh_email" autocomplete="email" required>
        </div>
        <div class="field">
          <label for="cf-who">{{ \App\field('cnt_who_label', __('I am…', 'sage')) }}</label>
          <select id="cf-who" name="mh_who">
            <option value="">{{ __('Choose one (optional)', 'sage') }}</option>
            <option value="developer">{{ __('A developer', 'sage') }}</option>
            <option value="learning">{{ __('Learning the web', 'sage') }}</option>
            <option value="business">{{ __('A shop or team', 'sage') }}</option>
            <option value="agency">{{ __('A marketing agency', 'sage') }}</option>
            <option value="other">{{ __('Something else', 'sage') }}</option>
          </select>
        </div>
        <div class="field">
          <label for="cf-subject">{{ __('Subject', 'matthummel') }}</label>
          <input id="cf-subject" type="text" name="mh_subject">
        </div>
        <div class="field">
          <label for="cf-message">{{ __('Message', 'matthummel') }}</label>
          <textarea id="cf-message" name="mh_message" rows="6" required></textarea>
        </div>
        <button class="btn" type="submit">{{ \App\field('cnt_submit', __('Send hello', 'sage')) }}</button>
      </form>
    </div>

    <aside class="contact-aside" aria-labelledby="elsewhere-heading">
      <h2 id="elsewhere-heading" class="display-title is-section">{{ \App\field('cnt_else_h2', __('Find me elsewhere', 'sage')) }}</h2>
      <p>{{ \App\field('cnt_aside', __('Prefer GitHub or LinkedIn? Those work too. Ridges & Valleys is the Gettysburg studio site.', 'sage')) }}</p>
      @include('partials.social', ['labeled' => true, 'links' => array_merge(\App\mh_social_links(), [[
        'key' => 'globe',
        'label' => 'Ridges & Valleys',
        'url' => 'https://ridgesandvalleys.com',
      ]])])
    </aside>
  </div>
@endsection
