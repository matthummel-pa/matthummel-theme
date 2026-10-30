{{-- Side-by-side comparison. Themes are the columns, the things to compare are the rows.
     Expects $themes (rows from \App\mh_shop_items()) (the section already names itself with its h2, so the scroll region gets its own label). --}}
@php
  $dash = '—';
@endphp
<div class="shop-compare" role="region" aria-label="{{ esc_attr__('Comparison table', 'sage') }}" tabindex="0">
  <table class="shop-compare__table">
    <caption class="visually-hidden">{{ __('Compare the themes: who each is for, price, what it works with, the live demo, and how to get it.', 'sage') }}</caption>
    <thead>
      <tr>
        <th scope="col" class="shop-compare__corner"><span class="visually-hidden">{{ __('What to compare', 'sage') }}</span></th>
        @foreach ($themes as $theme)
          <th scope="col" class="shop-compare__col shop-compare__col--{{ esc_attr($theme['status']) }}">
            {{-- Name first, state badge under it, so a screen reader says "WalkRidge, Coming soon". --}}
            <a href="{{ esc_url($theme['url']) }}">{{ $theme['title'] }}</a>
            <span class="shop-compare__state">{{ $theme['status'] === 'soon' ? __('Coming soon', 'sage') : __('Available now', 'sage') }}</span>
          </th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      <tr>
        <th scope="row">{{ __('Best for', 'sage') }}</th>
        @foreach ($themes as $theme)
          <td class="shop-compare__cell shop-compare__cell--{{ esc_attr($theme['status']) }}">{{ $theme['place'] !== '' ? $theme['place'] : $dash }}</td>
        @endforeach
      </tr>
      <tr>
        <th scope="row">{{ __('Price', 'sage') }}</th>
        @foreach ($themes as $theme)
          <td class="shop-compare__cell shop-compare__cell--{{ esc_attr($theme['status']) }}">
            @if ($theme['status'] === 'soon')
              {{ __('Coming soon', 'sage') }}
            @else
              <strong>{{ $theme['price'] !== '' ? $theme['price'] : $dash }}</strong>
            @endif
          </td>
        @endforeach
      </tr>
      <tr>
        <th scope="row">{{ __('Works with', 'sage') }}</th>
        @foreach ($themes as $theme)
          <td class="shop-compare__cell shop-compare__cell--{{ esc_attr($theme['status']) }}">{{ $theme['works_with'] !== '' ? $theme['works_with'] : $dash }}</td>
        @endforeach
      </tr>
      <tr>
        <th scope="row">{{ __('Live demo', 'sage') }}</th>
        @foreach ($themes as $theme)
          <td class="shop-compare__cell shop-compare__cell--{{ esc_attr($theme['status']) }}">
            @if ($theme['demo'] !== '')
              <a class="shop-link" href="{{ esc_url($theme['demo']) }}" rel="noopener" target="_blank">{{ __('Open demo', 'sage') }}<span class="visually-hidden">{{ sprintf(__(' for %s (opens in a new window)', 'sage'), $theme['title']) }}</span> <span class="shop-link__icon" aria-hidden="true">↗</span></a>
            @else
              {{ $dash }}
            @endif
          </td>
        @endforeach
      </tr>
      <tr>
        <th scope="row">{{ __('Get it', 'sage') }}</th>
        @foreach ($themes as $theme)
          <td class="shop-compare__cell shop-compare__cell--{{ esc_attr($theme['status']) }}">
            @if ($theme['status'] === 'buy')
              <a class="btn btn--sm" href="{{ esc_url($theme['url']) }}#buy">{{ __('Choose a plan', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('for %s', 'sage'), $theme['title']) }}</span></a>
            @elseif ($theme['status'] === 'soon')
              <a class="btn btn--sm btn-outline" href="{{ esc_url(home_url('/get-updates/')) }}">{{ __('Get updates', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('to hear when %s is ready', 'sage'), $theme['title']) }}</span></a>
            @else
              <a class="btn btn--sm btn-outline" href="{{ esc_url($theme['url']) }}">{{ __('View details', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('for %s', 'sage'), $theme['title']) }}</span></a>
            @endif
          </td>
        @endforeach
      </tr>
    </tbody>
  </table>
</div>
