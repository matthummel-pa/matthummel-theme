{{-- One resource link card. Expects $item from App\mh_resources_catalog_group(). --}}
@php
  $isAff = ! empty($item['affiliate']);
  $rel = \App\mh_outbound_rel($isAff);
@endphp
<li class="resources-card">
  <div class="resources-card__meta">
    @if (($item['badge'] ?? '') !== '')
      <span class="resources-card__badge">{{ $item['badge'] }}</span>
    @endif
    @if ($isAff)
      <span class="resources-card__aff">{{ __('Affiliate', 'sage') }}</span>
    @endif
  </div>
  <h3 class="resources-card__name">
    <a
      href="{{ esc_url($item['url']) }}"
      rel="{{ $rel }}"
      @if ($isAff) data-affiliate="true" class="affiliate-link" @endif
      @if (\App\mh_is_external_url($item['url'])) target="_blank" @endif
    >{{ $item['name'] }}@if (\App\mh_is_external_url($item['url']))<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>@endif</a>
  </h3>
  <p class="resources-card__blurb">{{ $item['blurb'] }}</p>
  <span class="resources-card__go{{ \App\mh_is_external_url($item['url']) ? ' resources-card__go--ext' : '' }}" aria-hidden="true">{{ \App\mh_is_external_url($item['url']) ? '↗' : '→' }}</span>
</li>
