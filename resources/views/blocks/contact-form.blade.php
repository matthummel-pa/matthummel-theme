{{--
  matthummel/contact-form: the shared contact form (partials/contact-form), handled in app/contact.php.
  Only visible wording is a block setting. The nonce, honeypot, field names and where the note goes are not.
--}}
@php
  $text = static fn (string $key): string => sanitize_text_field((string) ($attributes[$key] ?? ''));
@endphp
<div class="{{ trim('mh-contact-form contact-form-panel '.($attributes['className'] ?? '')) }}">
  @include('partials.contact-form', [
    'compact' => false,
    'formId' => 'contact-form',
    'statusId' => 'contact-status',
    'actionUrl' => (string) get_permalink(),
    'whoLabel' => $text('whoLabel') ?: __('Who you are', 'sage'),
    'messageHint' => $text('messageHint') ?: __('No pitch deck needed. Paste a URL if you have one.', 'sage'),
    'submitLabel' => $text('submitLabel') ?: __('Send note', 'sage'),
    'replyNote' => $text('replyNote') ?: \App\mh_reply_sla(),
    'successText' => $text('successMessage') ?: __('Thanks — I got it and will write back soon.', 'sage'),
    'errorText' => $text('errorMessage') ?: __('Something went wrong. Check the required fields and try again.', 'sage'),
  ])
</div>
