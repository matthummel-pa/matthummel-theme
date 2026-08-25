{{--
  Shared CTA: project discovery brief
  Used on Home + Services process sections.
--}}
@php
  $startUrl = home_url('/start/');
  $contactUrl = home_url('/contact/');
@endphp
<aside class="disc-cta" aria-labelledby="disc-cta-heading">
  <div class="disc-cta__visual" aria-hidden="true">
    <ol class="disc-cta__steps">
      <li class="disc-cta__step">
        <span class="disc-cta__num">01</span>
        <span class="disc-cta__label">You</span>
      </li>
      <li class="disc-cta__step">
        <span class="disc-cta__num">02</span>
        <span class="disc-cta__label">Project</span>
      </li>
      <li class="disc-cta__step">
        <span class="disc-cta__num">03</span>
        <span class="disc-cta__label">Goals</span>
      </li>
      <li class="disc-cta__step disc-cta__step--last">
        <span class="disc-cta__num">04</span>
        <span class="disc-cta__label">Send</span>
      </li>
    </ol>
  </div>

  <div class="disc-cta__copy">
    <p class="disc-cta__eyebrow" id="disc-cta-heading">Quick start</p>
    <p class="disc-cta__title">{{ $title ?? __('Start a project brief.', 'sage') }}</p>
    <p class="disc-cta__body">{{ $body ?? __('A short stepped form agencies and shops use in discovery. Takes about five minutes. I use it to prepare for our first meeting — so we skip the blank-page call.', 'sage') }}</p>
  </div>

  <div class="disc-cta__actions">
    <a class="btn" href="{{ esc_url($startUrl) }}">
      {!! \App\mh_svg_icon('check', 16) !!}
      {{ $cta ?? __('Start a brief', 'sage') }}
    </a>
    <a class="disc-cta__secondary" href="{{ esc_url($contactUrl) }}">{{ __('Or just write a note →', 'sage') }}</a>
  </div>
</aside>
