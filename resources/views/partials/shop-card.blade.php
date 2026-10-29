{{-- One product in the shop listing. Expects $item from \App\mh_shop_items(). Uses the existing work-card styles. --}}
@php
  $status = (string) $item['status'];
  $typeLabel = \App\mh_product_types()[$item['type']]['singular'] ?? '';
@endphp
<article class="work-card" id="{{ esc_attr($item['slug']) }}">
  @if ($item['image'] !== '')
    {{-- The image link is hidden from assistive tech: the title link below goes to the same page. --}}
    <a class="work-shot" href="{{ esc_url($item['url']) }}" aria-hidden="true" tabindex="-1">
      <img
        src="{{ esc_url($item['image']) }}"
        alt="{{ sprintf(__('%s screenshot', 'sage'), $item['title']) }}"
        width="960"
        height="540"
        loading="lazy"
        decoding="async"
      >
    </a>
  @endif
  <div class="work-body">
    <p class="project-type-row">
      <span class="spec-badge">{{ $typeLabel }}</span>
      @if ($status === 'soon')
        <span class="spec-badge">{{ __('Coming soon', 'sage') }}</span>
      @endif
    </p>
    <h3><a href="{{ esc_url($item['url']) }}">{{ $item['title'] }}</a></h3>
    @if ($item['blurb'] !== '')
      <p class="work-blurb">{{ $item['blurb'] }}</p>
    @endif
    @if ($item['price'] !== '' && $status !== 'soon')
      <p class="work-meta"><strong>{{ $item['price'] }}</strong></p>
    @endif
    @if (in_array($status, ['buy', 'free'], true) || $item['demo'] !== '')
      <p class="concept-hero-actions">
        @if ($status === 'buy')
          <a class="btn" href="{{ esc_url($item['url']) }}#buy">
            {!! \App\mh_svg_icon('cart', 15) !!}
            {{ __('Choose a plan', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('for %s', 'sage'), $item['title']) }}</span>
          </a>
        @elseif ($status === 'free')
          <a class="btn" href="{{ esc_url($item['free_url']) }}" rel="noopener">
            {!! \App\mh_svg_icon('download', 15) !!}
            {{ __('Download free', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('%s (zip)', 'sage'), $item['title']) }}</span>
          </a>
        @endif
        @if ($item['demo'] !== '')
          <a class="{{ in_array($status, ['buy', 'free'], true) ? 'h-text-arrow' : 'btn btn-outline' }}" href="{{ esc_url($item['demo']) }}" rel="noopener" target="_blank">
            {{ $item['type'] === 'plugin' ? __('Docs and demo', 'sage') : __('Live demo', 'sage') }}<span class="visually-hidden">{{ sprintf(__(' for %s (opens in a new window)', 'sage'), $item['title']) }}</span> <span aria-hidden="true">↗</span>
          </a>
        @endif
      </p>
    @endif
  </div>
</article>
