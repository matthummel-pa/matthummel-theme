@php
  $links = $links ?? \App\mh_social_links();
  $labeled = ! empty($labeled);
  $cards = ! empty($cards);
  $compact = ! empty($compact);
@endphp
@if ($links)
  <ul class="soc-list{{ $labeled ? ' soc-list--labeled' : '' }}{{ $cards ? ' soc-list--cards' : '' }}{{ $compact ? ' soc-list--compact' : '' }}">
    @foreach ($links as $s)
      <li>
        <a class="soc-link" href="{{ esc_url($s['url']) }}"@if (($s['key'] ?? '') !== 'rss') rel="me noopener" target="_blank"@else rel="me"@endif>
          {!! \App\mh_svg_icon($s['key']) !!}
          @if ($labeled)
            <span class="soc-link__copy">
              <span class="soc-link__label">{{ $s['label'] }}</span>
              @if ($cards && ! empty($s['note']))
                <span class="soc-link__note">{{ $s['note'] }}</span>
              @endif
            </span>
          @else
            <span class="visually-hidden">{{ $s['label'] }}</span>
          @endif
          @if (($s['key'] ?? '') !== 'rss')
            <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
          @endif
        </a>
      </li>
    @endforeach
  </ul>
@endif
