{{-- A short card for a theme that is still being built: small image, name, status, one line, and the Get updates button. Expects $item from \App\mh_shop_items(). --}}
@php
  $typeLabel = \App\mh_product_types()[$item['type']]['singular'] ?? '';
  $titleId = $item['slug'].'-upcoming-title';
@endphp
<article class="upcoming-card" aria-labelledby="{{ esc_attr($titleId) }}">
  @if ($item['image'] !== '')
    <a class="upcoming-card__shot" href="{{ esc_url($item['url']) }}" aria-hidden="true" tabindex="-1">
      <img src="{{ esc_url($item['image']) }}" alt="" width="960" height="540" loading="lazy" decoding="async">
    </a>
  @endif
  <div class="upcoming-card__body">
    <p class="project-type-row">
      @if ($typeLabel !== '')
        <span class="spec-badge">{{ $typeLabel }}</span>
      @endif
      <span class="spec-badge">{{ __('Coming soon', 'sage') }}</span>
    </p>
    <h3 class="upcoming-card__title" id="{{ esc_attr($titleId) }}"><a href="{{ esc_url($item['url']) }}">{{ $item['title'] }}</a></h3>
    @if ($item['place'] !== '')
      <p class="upcoming-card__for"><span>{{ __('Best for', 'sage') }}</span> {{ $item['place'] }}</p>
    @endif
    @if ($item['blurb'] !== '')
      <p class="upcoming-card__blurb">{{ $item['blurb'] }}</p>
    @endif
    <a class="shop-link shop-link--forward upcoming-card__cta" href="{{ esc_url(home_url('/get-updates/')) }}">
      {{ __('Get updates', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('to hear when %s is ready', 'sage'), $item['title']) }}</span>
      <span class="shop-link__icon" aria-hidden="true">&rarr;</span>
    </a>
  </div>
</article>
