@php
  $links = $links ?? \App\mh_social_links();
  $labeled = ! empty($labeled);
@endphp
@if ($links)
  <ul class="soc-list{{ $labeled ? ' soc-list--labeled' : '' }}">
    @foreach ($links as $s)
      <li>
        <a class="soc-link" href="{{ esc_url($s['url']) }}" rel="me noopener" target="_blank">
          {!! \App\mh_svg_icon($s['key']) !!}
          @if ($labeled)
            <span>{{ $s['label'] }}</span>
          @else
            <span class="visually-hidden">{{ $s['label'] }}</span>
          @endif
          <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
        </a>
      </li>
    @endforeach
  </ul>
@endif
