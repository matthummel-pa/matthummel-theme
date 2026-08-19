{{--
  Template Name: Contact
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container">
    <p class="eyebrow">Contact</p>
    <h1 class="display-title is-hero">Write me a short note.</h1>
    <p class="lead">Gettysburg, PA. I usually reply in one or two business days. Best for blog notes, a WordPress or Power Platform question, or a small project.</p>
  </header>

  <div class="contact-wrap">
    @php($mhStatus = isset($_GET['contact']) ? sanitize_key($_GET['contact']) : '')
    @if ($mhStatus === 'success')
      <p class="form-success" role="status">{{ __('Thanks. I got it and will write back soon.', 'matthummel') }}</p>
    @elseif ($mhStatus === 'error')
      <p class="form-error" role="alert">{{ __('Something went wrong. Check the fields and try again.', 'matthummel') }}</p>
    @endif

    @if (get_the_content())
      <div class="entry-content post-prose">@php(the_content())</div>
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
        <label for="cf-subject">{{ __('Subject', 'matthummel') }}</label>
        <input id="cf-subject" type="text" name="mh_subject">
      </div>
      <div class="field">
        <label for="cf-message">{{ __('Message', 'matthummel') }}</label>
        <textarea id="cf-message" name="mh_message" rows="6" required></textarea>
      </div>
      <button class="btn" type="submit">{{ __('Send', 'matthummel') }}</button>
    </form>

    <h2 class="display-title is-section" style="margin-top:2.5rem">Elsewhere</h2>
    <ul class="elsewhere">
      <li><a href="https://github.com/matthummel-pa" rel="me">GitHub</a></li>
      <li><a href="https://dev.to/matthummel" rel="me">DEV.to</a></li>
      <li><a href="https://bsky.app/profile/matthummel.bsky.social" rel="me">Bluesky</a></li>
      <li><a href="https://www.reddit.com/user/matt-hummel" rel="me">Reddit</a></li>
      <li><a href="https://www.linkedin.com/in/matt-hummel-pa" rel="me">LinkedIn</a></li>
      <li><a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a></li>
    </ul>
  </div>
@endsection
