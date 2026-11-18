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
      @if ($recoveryVisible === '')
        <p>{{ __('This page is for a theme payment that did not go through. The form lets you update your card. I never see the card number.', 'sage') }}</p>
        <p>{{ __('If the form does not open, write to me at hello@matthummel.com. I reply within one business day, Eastern Time.', 'sage') }}</p>
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
