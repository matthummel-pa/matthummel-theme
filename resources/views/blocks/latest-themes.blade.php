{{-- matthummel/latest-themes: the newest themes from the shop, newest first (the shop list keeps project date order within each status). --}}
@php
  $count = min(6, max(1, (int) ($attributes['count'] ?? 3)));
  $showSoon = ! empty($attributes['showSoon']);
  $onlySoon = ! empty($attributes['onlySoon']);
  // Themes on sale come first; "coming soon" ones follow only when the block asks for them.
  $items = array_slice(array_values(array_filter(
    \App\mh_shop_items('theme'),
    static fn (array $item): bool => $onlySoon ? $item['status'] === 'soon' : ($showSoon || $item['status'] !== 'soon')
  )), 0, $count);
  $linkText = trim((string) ($attributes['linkText'] ?? ''));
@endphp
<div class="{{ trim('mh-data mh-latest-themes '.($attributes['className'] ?? '')) }}">
  @if ($linkText !== '')
    <div class="mh-data__meta">
      <a class="shop-link shop-link--forward" href="{{ esc_url(home_url('/themes/')) }}">{{ $linkText }} <span class="shop-link__icon" aria-hidden="true">&rarr;</span></a>
    </div>
  @endif
  @if ($items !== [])
    <div class="h-project-grid">
      @foreach ($items as $item)
        @include('partials.shop-card', ['item' => $item])
      @endforeach
    </div>
  @else
    <p class="h-journal__empty">{{ $onlySoon ? __('Nothing is in progress right now. New themes show here when I start them.', 'sage') : __('Themes are on the way.', 'sage') }}</p>
  @endif
</div>
