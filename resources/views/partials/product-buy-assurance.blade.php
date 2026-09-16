{{-- Guarantee + install upsell next to Add to cart. Expects $guarantee, $helpUrl, $isFree, $isService. --}}
@if (! empty($guarantee))
  <p class="pf-buy-guarantee">
    <span class="pf-buy-guarantee__icon" aria-hidden="true">{!! \App\mh_svg_icon('check', 15) !!}</span>
    <span>{{ $guarantee }}</span>
  </p>
@endif
@if (empty($isFree) && empty($isService))
  <p class="pf-buy-upsell">
    <a href="{{ esc_url($helpUrl) }}">{{ \App\mh_product_install_help_copy() }} <span aria-hidden="true">→</span></a>
  </p>
@endif
