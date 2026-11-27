{{-- Analytics consent. Hidden until resources/js/consent.js finds no saved choice. Only prints when the container does. --}}
@if (\App\mh_gtm_container_id() !== '')
  <style>
    #mh-consent{position:fixed;inset:auto 1rem 1rem 1rem;z-index:60;max-width:34rem;margin-inline:auto;padding:1.1rem 1.25rem;border:1px solid #cbd5e1;border-radius:.75rem;background:#fff;color:#0f172a;box-shadow:0 10px 30px rgb(15 23 42 / .18);font:400 1rem/1.5 inherit}
    #mh-consent[hidden]{display:none}
    html.mh-dark #mh-consent{background:#0f172a;color:#f1f5f9;border-color:#334155}
    #mh-consent h2{margin:0 0 .35rem;font-size:1.05rem;line-height:1.3}
    #mh-consent p{margin:0 0 .9rem}
    #mh-consent a{color:inherit;text-decoration:underline}
    #mh-consent .mh-consent-actions{display:flex;flex-wrap:wrap;gap:.6rem}
    #mh-consent button{min-height:2.75rem;padding:.5rem 1.1rem;border:2px solid #1d4ed8;border-radius:.5rem;font:600 1rem/1.2 inherit;cursor:pointer;background:#1d4ed8;color:#fff}
    #mh-consent button[data-consent="denied"]{background:transparent;color:#1d4ed8}
    html.mh-dark #mh-consent button{border-color:#93c5fd;background:#93c5fd;color:#0f172a}
    html.mh-dark #mh-consent button[data-consent="denied"]{background:transparent;color:#93c5fd}
    #mh-consent button:focus-visible,#mh-consent a:focus-visible{outline:3px solid #f59e0b;outline-offset:2px}
  </style>
  <div id="mh-consent" role="region" aria-labelledby="mh-consent-title" hidden tabindex="-1" @if (\App\mh_google_ads_enabled()) data-ads="1" @endif>
    @if (\App\mh_google_ads_enabled())
      <h2 id="mh-consent-title">{{ __('Analytics and ad cookies', 'sage') }}</h2>
      <p>{{ __('I use Google Analytics to see which pages get read, and Google Ads to measure which ads lead to a purchase. Both set cookies only if you say yes. Either answer is fine, and you can change it from the footer.', 'sage') }}
    @else
      <h2 id="mh-consent-title">{{ __('Analytics cookies', 'sage') }}</h2>
      <p>{{ __('I use Google Analytics to see which pages get read. It sets cookies only if you say yes. Either answer is fine, and you can change it from the footer.', 'sage') }}
    @endif
      <a href="{{ esc_url(\App\mh_privacy_policy_url()) }}">{{ __('Privacy', 'sage') }}</a></p>
    <div class="mh-consent-actions">
      <button type="button" data-consent="granted">{{ \App\mh_google_ads_enabled() ? __('Allow cookies', 'sage') : __('Allow analytics', 'sage') }}</button>
      <button type="button" data-consent="denied">{{ __('No thanks', 'sage') }}</button>
    </div>
  </div>
@endif
