{{-- Accessible breadcrumb for WooCommerce surfaces. --}}
@php
  $items = $items ?? [];
@endphp
@if (! empty($items))
  <nav class="woo-crumb" aria-label="{{ __('Breadcrumb', 'sage') }}">
    <ol class="woo-crumb__list">
      @foreach ($items as $i => $item)
        <li class="woo-crumb__item">
          @if ($i > 0)
            <span class="woo-crumb__sep" aria-hidden="true">/</span>
          @endif
          @if (! empty($item['url']) && empty($item['current']))
            <a class="woo-crumb__link" href="{{ esc_url($item['url']) }}">{{ $item['label'] }}</a>
          @else
            <span class="woo-crumb__current" @if (! empty($item['current'])) aria-current="page" @endif>{{ $item['label'] }}</span>
          @endif
        </li>
      @endforeach
    </ol>
  </nav>
@endif
