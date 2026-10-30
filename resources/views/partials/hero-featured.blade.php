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
    <p class="hero-feat__meta">
      @if ($featured['price'] !== '')
        <span class="spec-badge">{{ $featured['price'] }}</span>
      @endif
      <a class="h-text-arrow" href="{{ esc_url($featured['url']) }}">{{ __('See the theme', 'sage') }}</a>
    </p>
  </aside>
@endif
