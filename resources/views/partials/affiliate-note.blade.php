{{-- The affiliate disclosure note. Wording comes from App\mh_affiliate_disclosure_note(). --}}
<aside class="{{ trim('affiliate-note '.($class ?? '')) }}" role="note" aria-label="{{ esc_attr__('Affiliate disclosure', 'sage') }}">
  <p>
    <span class="affiliate-note__text">
      <strong>{{ __('Affiliate disclosure:', 'sage') }}</strong>
      {{ \App\mh_affiliate_disclosure_note() }}
    </span>
    <a class="shop-link shop-link--forward" href="{{ esc_url(\App\mh_affiliate_disclosure_url()) }}">{{ __('How affiliate links work', 'sage') }} <span class="shop-link__icon" aria-hidden="true">&rarr;</span></a>
  </p>
</aside>
