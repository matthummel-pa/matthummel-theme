@php
  $smsHref = \App\mh_now_sms_href();
  $noteStatus = 'now-note-status';
@endphp

<section class="now-block now-note" id="note" data-now-note data-invalid="{{ esc_attr__('Check your name, email, and note, then try again.', 'sage') }}" aria-labelledby="now-note-heading">
  <div class="now-block__head">
    <div class="now-block__icon" aria-hidden="true">{!! \App\mh_svg_icon('comment', 18) !!}</div>
    <div>
      <p class="now-block__eyebrow">{{ __('Inbox', 'sage') }}</p>
      <h2 id="now-note-heading" class="now-block__title">{{ \App\field('now_note_h2', __('Send a note', 'sage')) }}</h2>
    </div>
  </div>
  <p>{{ \App\field('now_note_lede', __('This form emails me. I usually reply within one business day (ET).', 'sage')) }}</p>
  <p class="form-error" data-now-note-error hidden tabindex="-1"></p>
  @include('partials.contact-form', [
    'compact' => true,
    'formId' => 'now-note-form',
    'statusId' => $noteStatus,
    'actionUrl' => get_permalink() ?: home_url('/now/'),
    'presetSubject' => __('Note from the Now page', 'sage'),
    'submitLabel' => \App\field('now_note_submit', __('Send note', 'sage')),
    'compactWhoFallback' => '',
    'messageRows' => 4,
  ])
  @if ($smsHref !== '')
    <p class="now-note__sms-note">{{ __('Text me opens the SMS app on your phone. The form is the inbox I read.', 'sage') }}</p>
    <a class="btn btn-outline now-note__sms" href="{{ esc_url($smsHref) }}">{{ __('Text me', 'sage') }}</a>
  @endif
</section>
