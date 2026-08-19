{{--
  Template Name: Contact
--}}
@extends('layouts.app')

@section('content')
  <header class="page-header container">
    <h1 class="display-title is-hero">{{ \App\field('cnt_h1', __('Say hello.', 'sage')) }}</h1>
    <p class="lead">{{ \App\field('cnt_lede', __('Questions about a post, a snippet, or GitHub are welcome. So is a note from a shop or a team that needs a WordPress or Power Platform hand. I usually reply in one or two business days.', 'sage')) }}</p>
  </header>

  <div class="contact-wrap">
    @php($mhStatus = isset($_GET['contact']) ? sanitize_key($_GET['contact']) : '')
    @if ($mhStatus === 'success')
      <p class="form-success" role="status">{{ \App\field('cnt_success', __('Thanks. I got it and will write back soon.', 'sage')) }}</p>
    @elseif ($mhStatus === 'error')
      <p class="form-error" role="alert">{{ \App\field('cnt_error', __('Something went wrong. Check the fields and try again.', 'sage')) }}</p>
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
      <button class="btn" type="submit">{{ \App\field('cnt_submit', __('Send hello', 'sage')) }}</button>
    </form>

    <h2 class="display-title is-section" style="margin-top:2.5rem">{{ \App\field('cnt_else_h2', __('Find me elsewhere', 'sage')) }}</h2>
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
