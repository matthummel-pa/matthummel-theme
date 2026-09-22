{{--
  Shared contact form. Compact mode is for project pages (prefilled, fewer fields).
--}}
@php
  $compact = ! empty($compact);
  $projectSlug = sanitize_title((string) ($projectSlug ?? ''));
  $projectTitle = (string) ($projectTitle ?? '');
  $formId = (string) ($formId ?? ($compact ? 'project-contact-form' : 'contact-form'));
  $statusId = (string) ($statusId ?? ($compact ? 'project-contact-status' : 'contact-status'));
  $actionUrl = (string) ($actionUrl ?? get_permalink());
  $mhStatus = isset($_GET['contact']) ? sanitize_key(wp_unslash($_GET['contact'])) : '';
  $mhError = $mhStatus === 'error';
  $mhOk = $mhStatus === 'success';
  $oldName = \App\mh_contact_old('name');
  $oldEmail = \App\mh_contact_old('email');
  $oldWho = \App\mh_contact_prefill('who');
  $oldSubject = \App\mh_contact_prefill('subject');
  $oldMessage = \App\mh_contact_prefill('message');
  $invalid = \App\mh_contact_old_errors();
  $nameId = $compact ? 'pcf-name' : 'cf-name';
  $emailId = $compact ? 'pcf-email' : 'cf-email';
  $whoId = $compact ? 'pcf-who' : 'cf-who';
  $subjectId = $compact ? 'pcf-subject' : 'cf-subject';
  $messageId = $compact ? 'pcf-message' : 'cf-message';
  $hpId = $compact ? 'pcf-company' : 'cf-company';
  if ($compact && $projectTitle !== '') {
    if ($oldSubject === '') {
      $oldSubject = sprintf(__('Note about %s', 'sage'), $projectTitle);
    }
    if ($oldMessage === '') {
      $oldMessage = sprintf(__('I have a question about "%s".', 'sage'), $projectTitle);
    }
    if ($oldWho === '') {
      $oldWho = 'business';
    }
  }
@endphp

@if ($mhOk)
  <p class="form-success" id="{{ $statusId }}" role="status" tabindex="-1">
    {!! \App\mh_svg_icon('check', 18) !!}
    {{ \App\field('cnt_success', __('Thanks — I got it and will write back soon.', 'sage')) }}
  </p>
@elseif ($mhError)
  <p class="form-error" id="{{ $statusId }}" role="alert" tabindex="-1">
    {{ \App\field('cnt_error', __('Something went wrong. Check the required fields and try again.', 'sage')) }}
  </p>
@endif

<form class="contact-form{{ $mhError ? ' is-error' : '' }}{{ $compact ? ' contact-form--compact' : '' }}" id="{{ $formId }}" method="post" action="{{ esc_url($actionUrl) }}" novalidate>
  @php(wp_nonce_field('mh_contact', 'mh_contact_nonce'))
  <input type="hidden" name="action" value="mh_contact">
  <input type="hidden" name="mh_return" value="{{ esc_url(get_permalink()) }}">
  @if ($projectSlug !== '')
    <input type="hidden" name="mh_project" value="{{ esc_attr($projectSlug) }}">
  @endif
  <p class="hp visually-hidden">
    <label for="{{ $hpId }}">{{ __('Company (leave empty)', 'sage') }}</label>
    <input id="{{ $hpId }}" type="text" name="mh_hp" value="" tabindex="-1" autocomplete="off">
  </p>

  <div class="contact-form__row">
    <div class="field">
      <label for="{{ $nameId }}">{{ __('Name', 'sage') }} <span class="field-req" aria-hidden="true">*</span></label>
      <input id="{{ $nameId }}" type="text" name="mh_name" autocomplete="name" required aria-required="true" placeholder="{{ esc_attr__('Your name', 'sage') }}" value="{{ $oldName }}"@if (in_array('name', $invalid, true)) aria-invalid="true" aria-describedby="{{ $statusId }}"@endif>
    </div>
    <div class="field">
      <label for="{{ $emailId }}">{{ __('Email', 'sage') }} <span class="field-req" aria-hidden="true">*</span></label>
      <input id="{{ $emailId }}" type="email" name="mh_email" autocomplete="email" inputmode="email" required aria-required="true" placeholder="you@example.com" aria-describedby="{{ $emailId }}-hint{{ in_array('email', $invalid, true) ? ' '.$statusId : '' }}" value="{{ $oldEmail }}"@if (in_array('email', $invalid, true)) aria-invalid="true"@endif>
      <p class="field-hint" id="{{ $emailId }}-hint">{{ __('I only use this to reply. No newsletter.', 'sage') }}</p>
    </div>
  </div>

  @if (! $compact)
    <div class="field">
      <label for="{{ $whoId }}">{{ \App\field('cnt_who_label', __('Who you are', 'sage')) }} <span class="field-opt">{{ __('(optional — helps me reply in the right shape)', 'sage') }}</span></label>
      <select id="{{ $whoId }}" name="mh_who" autocomplete="off">
        <option value=""@if ($oldWho === '') selected @endif>{{ __('Choose one', 'sage') }}</option>
        <option value="developer"@if ($oldWho === 'developer') selected @endif>{{ __('A developer', 'sage') }}</option>
        <option value="recruiter"@if ($oldWho === 'recruiter') selected @endif>{{ __('A recruiter or hiring manager', 'sage') }}</option>
        <option value="business"@if ($oldWho === 'business') selected @endif>{{ __('A shop or small business', 'sage') }}</option>
        <option value="agency"@if ($oldWho === 'agency') selected @endif>{{ __('A marketing or design agency', 'sage') }}</option>
        <option value="learning"@if ($oldWho === 'learning') selected @endif>{{ __('Someone learning web development', 'sage') }}</option>
        <option value="other"@if ($oldWho === 'other') selected @endif>{{ __('Something else', 'sage') }}</option>
      </select>
    </div>

    <div class="field">
      <label for="{{ $subjectId }}">{{ __('Subject', 'sage') }} <span class="field-opt">{{ __('(optional)', 'sage') }}</span></label>
      <input id="{{ $subjectId }}" type="text" name="mh_subject" autocomplete="off" placeholder="{{ esc_attr__('e.g. WordPress platform or web application', 'sage') }}" value="{{ $oldSubject }}">
    </div>
  @else
    <input type="hidden" name="mh_who" value="{{ esc_attr($oldWho !== '' ? $oldWho : 'business') }}">
    <input type="hidden" name="mh_subject" value="{{ esc_attr($oldSubject) }}">
  @endif

  <div class="field">
    <label for="{{ $messageId }}">{{ __('Message', 'sage') }} <span class="field-req" aria-hidden="true">*</span></label>
    <textarea id="{{ $messageId }}" name="mh_message" rows="{{ $compact ? 5 : 7 }}" required aria-required="true" placeholder="{{ esc_attr__('Tell me what you would change, or what you need. A few sentences is plenty.', 'sage') }}" aria-describedby="{{ $messageId }}-hint{{ in_array('message', $invalid, true) ? ' '.$statusId : '' }}"@if (in_array('message', $invalid, true)) aria-invalid="true"@endif>{{ $oldMessage }}</textarea>
    <p class="field-hint" id="{{ $messageId }}-hint">{{ \App\field('cnt_message_hint', __('No pitch deck needed. Paste a URL if you have one.', 'sage')) }}</p>
  </div>

  <div class="contact-form__actions">
    <button class="btn" type="submit">
      {!! \App\mh_svg_icon('mail', 16) !!}
      {{ \App\field('cnt_submit', __('Send note', 'sage')) }}
    </button>
    <p class="field-hint">{{ \App\field('cnt_reply_note', \App\mh_reply_sla()) }}</p>
  </div>
</form>
