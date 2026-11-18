{{--
  Template Name: Payment recovery

  Freemius failed-payment emails land here when Plans → Customization uses this page
  as the payment recovery URL. The checkout script has to be on the page or the
  card form does not open. Copy lives in the page body.
--}}
@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
      $recoveryRaw = (string) get_the_content();
      $recoveryVisible = trim(wp_strip_all_tags($recoveryRaw));
      $recoveryHasScript = str_contains($recoveryRaw, 'checkout.freemius.com/js/v1/');
    @endphp
    @include('partials.page-header')
    <div class="container measure page-block">
      <p id="mh-recovery-status" role="status" hidden data-done="{{ __('Your card is saved. Freemius will try the charge again and email you the result.', 'sage') }}"></p>
      {{-- This has to run before the checkout script, including a script pasted into the page body. --}}
      <script>
        window.FS = window.FS || {};
        window.FS.paymentMethodUpdateEvents = {
          success: function () {
            var note = document.getElementById('mh-recovery-status');
            if (!note) {
              return;
            }
            note.hidden = false;
            note.textContent = note.getAttribute('data-done') || '';
          }
        };
      </script>
      @if ($recoveryVisible === '')
        <p>{{ __('This license payment did not go through. The form puts a new card on file. I never see the card number.', 'sage') }}</p>
        <p>{{ __('Freemius will try the charge again with the new card and email you the result.', 'sage') }}</p>
        <p>
          {{ __('If the form does not open, email me. I reply within one business day, Eastern Time.', 'sage') }}
          <a href="{{ esc_url('mailto:hello@matthummel.com') }}">hello@matthummel.com</a>
        </p>
      @else
        @include('partials.content-page')
      @endif
      @if (! $recoveryHasScript)
        {{-- data-no-optimize / data-no-defer: LiteSpeed would otherwise hold this until a tap, and the card form would not open. --}}
        <script src="https://checkout.freemius.com/js/v1/" data-no-optimize="1" data-no-defer="1" data-cfasync="false"></script>
      @endif
    </div>
  @endwhile
@endsection
