{{-- Side-by-side table of the themes. Expects $themes (rows from \App\mh_shop_items()) and $labelId (heading id). --}}
<div class="shop-compare" role="region" aria-labelledby="{{ esc_attr($labelId) }}" tabindex="0">
  <table>
    <caption class="visually-hidden">{{ __('Compare the themes: who each is for, price, what it works with, and the live demo.', 'sage') }}</caption>
    <thead>
      <tr>
        <th scope="col">{{ __('Theme', 'sage') }}</th>
        <th scope="col">{{ __('Best for', 'sage') }}</th>
        <th scope="col">{{ __('Price', 'sage') }}</th>
        <th scope="col">{{ __('Works with', 'sage') }}</th>
        <th scope="col">{{ __('Demo', 'sage') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($themes as $theme)
        <tr>
          <th scope="row"><a href="{{ esc_url($theme['url']) }}">{{ $theme['title'] }}</a></th>
          <td>{{ $theme['place'] !== '' ? $theme['place'] : '—' }}</td>
          <td>{{ $theme['price'] !== '' ? $theme['price'] : '—' }}</td>
          <td>{{ $theme['works_with'] !== '' ? $theme['works_with'] : '—' }}</td>
          <td>
            @if ($theme['demo'] !== '')
              <a href="{{ esc_url($theme['demo']) }}" rel="noopener" target="_blank">{{ __('Open demo', 'sage') }}<span class="visually-hidden">{{ sprintf(__(' for %s (opens in a new window)', 'sage'), $theme['title']) }}</span> <span aria-hidden="true">↗</span></a>
            @else
              —
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
