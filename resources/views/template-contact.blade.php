{{--
  Template Name: Contact
--}}

@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  <div class="contact-wrap">
    @php($mhStatus = isset($_GET['contact']) ? sanitize_key($_GET['contact']) : '')
    @if ($mhStatus === 'success')
      <p class="form-success">{{ __("Thanks — your message has been sent. I'll get back to you soon.", 'matthummel') }}</p>
    @elseif ($mhStatus === 'error')
      <p class="form-error">{{ __('Sorry, something went wrong. Please check the fields and try again.', 'matthummel') }}</p>
    @endif

    @php($mhContactIntro = get_theme_mod('mh_contact_intro', ''))
    @if ($mhContactIntro)
      <div class="entry-content post-prose" style="padding-inline:0">{!! wp_kses_post(wpautop($mhContactIntro)) !!}</div>
    @endif

    @if (get_the_content())
      <div class="entry-content post-prose" style="padding-inline:0">
        @php(the_content())
      </div>
    @endif

    <form class="contact-form" method="post" action="">
      @php(wp_nonce_field('mh_contact', 'mh_contact_nonce'))
      <input type="hidden" name="action" value="mh_contact">
      <p class="hp"><label>{{ __('Leave this field empty', 'matthummel') }}<input type="text" name="mh_hp" tabindex="-1" autocomplete="off"></label></p>

      <div class="field">
        <label for="cf-name">{{ __('Name', 'matthummel') }}</label>
        <input id="cf-name" type="text" name="mh_name" required>
      </div>
      <div class="field">
        <label for="cf-email">{{ __('Email', 'matthummel') }}</label>
        <input id="cf-email" type="email" name="mh_email" required>
      </div>
      <div class="field">
        <label for="cf-subject">{{ __('Subject', 'matthummel') }}</label>
        <input id="cf-subject" type="text" name="mh_subject">
      </div>
      <div class="field">
        <label for="cf-message">{{ __('Message', 'matthummel') }}</label>
        <textarea id="cf-message" name="mh_message" rows="6" required></textarea>
      </div>
      <button class="btn" type="submit">{{ __('Send message', 'matthummel') }}</button>
    </form>
  </div>
@endsection
