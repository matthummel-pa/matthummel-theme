{{-- matthummel/resource-catalog: link cards from one group of App\mh_resources_catalog(). Affiliate links keep their label, rel="sponsored" and data-affiliate. --}}
@php
  $group = \App\mh_resources_catalog_group((string) ($attributes['section'] ?? 'starters'));
  $items = $group['items'] ?? [];
  $hasAff = false;
  foreach ($items as $item) {
    $hasAff = $hasAff || ! empty($item['affiliate']);
  }
@endphp
@if ($items !== [])
  <div class="{{ trim('mh-data mh-resource-catalog '.($attributes['className'] ?? '')) }}">
    @if ($hasAff && \App\mh_affiliate_note_is_missing())
      @include('partials.affiliate-note')
    @endif
    @if (($attributes['showCount'] ?? true) !== false)
      {{-- translators: %s: number of links in this section. --}}
      <p class="resources-section__count">{{ sprintf(_n('%s link', '%s links', count($items), 'sage'), number_format_i18n(count($items))) }}</p>
    @endif
    <ul class="resources-list">
      @foreach ($items as $item)
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
          <span class="resources-card__go" aria-hidden="true">{{ \App\mh_is_external_url($item['url']) ? '↗' : '→' }}</span>
        </li>
      @endforeach
    </ul>
  </div>
@endif
