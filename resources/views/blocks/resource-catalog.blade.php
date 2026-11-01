{{-- matthummel/resource-catalog: link cards from one group of App\mh_resources_catalog(). Affiliate links keep their label, rel="sponsored" and data-affiliate. --}}
@php
  $group = \App\mh_resources_catalog_group((string) ($attributes['section'] ?? 'starters'));
  $items = $group['items'] ?? [];
  $limit = max(0, (int) ($attributes['limit'] ?? 0));
  $shown = $limit > 0 ? array_slice($items, 0, $limit) : $items;
  $more = $limit > 0 ? array_slice($items, $limit) : [];
  $cardView = (string) ($attributes['section'] ?? 'starters') === 'tools' ? 'partials.tool-card' : 'partials.resource-card';
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
    <ul class="resources-list{{ $cardView === 'partials.tool-card' ? ' resources-list--tools' : '' }}">
      @foreach ($shown as $item)
        @include($cardView, ['item' => $item])
      @endforeach
    </ul>
    @if ($more !== [])
      <details class="resources-more">
        <summary>
          {{-- translators: %s: number of extra links. --}}
          <span>{{ sprintf(_n('Show %s more link', 'Show %s more links', count($more), 'sage'), number_format_i18n(count($more))) }}</span>
        </summary>
        <ul class="resources-list{{ $cardView === 'partials.tool-card' ? ' resources-list--tools' : '' }}">
          @foreach ($more as $item)
            @include($cardView, ['item' => $item])
          @endforeach
        </ul>
      </details>
    @endif
  </div>
@endif
