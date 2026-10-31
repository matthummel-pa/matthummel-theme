{{-- Home hero "Featured theme": the newest product that has a screenshot, as one card beside the copy. --}}
{{-- Real content (a link to a product), so it is not aria-hidden. Renders nothing when there is no product. --}}
@php
  $featured = null;
  foreach (\App\mh_shop_items('theme') as $candidate) {
    if ($candidate['status'] !== 'soon' && $candidate['image'] !== '') {
      $featured = $candidate;
      break;
    }
  }
@endphp
@if ($featured)
  <aside class="hero-feat" aria-labelledby="hero-feat-title">
    <p class="hero-feat__kicker">{{ __('Newest theme', 'sage') }}</p>
    <a class="hero-feat__shot" href="{{ esc_url($featured['url']) }}" aria-hidden="true" tabindex="-1">
      <img src="{{ esc_url($featured['image']) }}" alt="" width="960" height="540" decoding="async" fetchpriority="high">
    </a>
    <h2 class="hero-feat__title" id="hero-feat-title"><a href="{{ esc_url($featured['url']) }}">{{ $featured['title'] }}</a></h2>
    @if ($featured['blurb'] !== '')
      <p class="hero-feat__blurb">{{ $featured['blurb'] }}</p>
    @endif
    <div class="hero-feat__meta">
      @if ($featured['price'] !== '')
        @php [$priceFrom, $priceAmount, $pricePer] = $featured['price_parts'] ?? ['', $featured['price'], ''] @endphp
        <p class="hero-feat__price shop-price">
          @if ($priceFrom !== '')<span class="shop-price__from">{{ $priceFrom }}</span>@endif
          <strong class="shop-price__amount">{{ $priceAmount }}</strong>
          @if ($pricePer !== '')<span class="shop-price__per">{{ $pricePer }}</span>@endif
        </p>
      @endif
      <a class="shop-link shop-link--forward" href="{{ esc_url($featured['url']) }}">
        {{ __('See the theme', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('%s', 'sage'), $featured['title']) }}</span>
        <span class="shop-link__icon" aria-hidden="true">→</span>
      </a>
    </div>
  </aside>
@endif
