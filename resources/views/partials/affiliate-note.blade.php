{{-- The affiliate disclosure note. Wording comes from App\mh_affiliate_disclosure_note(). --}}
<aside class="{{ trim('affiliate-note '.($class ?? '')) }}" role="note" aria-label="{{ esc_attr__('Affiliate disclosure', 'sage') }}">
  <p>
    <strong>{{ __('Affiliate disclosure:', 'sage') }}</strong>
    {{ \App\mh_affiliate_disclosure_note() }}
    <a href="{{ esc_url(\App\mh_affiliate_disclosure_url()) }}">{{ __('How affiliate links work', 'sage') }}</a>
  </p>
</aside>
