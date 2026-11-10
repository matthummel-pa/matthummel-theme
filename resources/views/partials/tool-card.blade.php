{{-- One tool in "Tools I recommend". Expects $item from App\mh_resources_catalog_group(): icon, badge, name, blurb, use, fit, url. --}}
@php
  $isAff = ! empty($item['affiliate']);
  $external = \App\mh_is_external_url($item['url']);
  $titleId = 'tool-'.sanitize_title($item['name']);
@endphp
<li class="tool-card" aria-labelledby="{{ esc_attr($titleId) }}">
  <div class="tool-card__top">
    <span class="tool-card__icon" aria-hidden="true">{!! \App\mh_svg_icon($item['icon'] ?? 'code', 22) !!}</span>
    <span class="tool-card__tags">
      @if (($item['badge'] ?? '') !== '')
        <span class="resources-card__badge">{{ $item['badge'] }}</span>
      @endif
      @if ($isAff)
        <span class="resources-card__aff">{{ __('Affiliate', 'sage') }}</span>
      @endif
    </span>
  </div>
  <h3 class="tool-card__name" id="{{ esc_attr($titleId) }}">{{ $item['name'] }}</h3>
  <p class="tool-card__blurb">{{ $item['blurb'] }}</p>
  <dl class="tool-card__facts">
    @if (($item['use'] ?? '') !== '')
      <div><dt>{{ $item['use_label'] ?? __('I use it for', 'sage') }}</dt><dd>{{ $item['use'] }}</dd></div>
    @endif
    @if (($item['fit'] ?? '') !== '')
      <div><dt>{{ $item['fit_label'] ?? __('Good fit if', 'sage') }}</dt><dd>{{ $item['fit'] }}</dd></div>
    @endif
  </dl>
  <a
    class="shop-link{{ $external ? '' : ' shop-link--forward' }} tool-card__cta{{ $isAff ? ' affiliate-link' : '' }}"
    href="{{ esc_url($item['url']) }}"
    rel="{{ \App\mh_outbound_rel($isAff) }}"
    @if ($isAff) data-affiliate="true" @endif
    @if ($external) target="_blank" @endif
  >
    {{ $item['cta'] ?? sprintf(__('Visit %s', 'sage'), $item['name']) }}<span class="visually-hidden"> {{ $item['name'] }}</span>@if ($external)<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>@endif
    <span class="shop-link__icon" aria-hidden="true">{{ $external ? '↗' : '→' }}</span>
  </a>
</li>
