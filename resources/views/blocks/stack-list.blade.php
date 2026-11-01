{{-- matthummel/stack-list: the groups from App\mh_uses_sections(). Affiliate links keep their label, rel="sponsored" and data-affiliate. --}}
@php
  $stack = \App\mh_uses_sections();
  $hasAff = false;
  foreach ($stack as $group) {
    foreach ($group['items'] as $row) {
      $hasAff = $hasAff || ! empty($row[3]);
    }
  }
@endphp
<div class="{{ trim('mh-data mh-stack-list '.($attributes['className'] ?? '')) }}">
  @if ($hasAff && \App\mh_affiliate_note_is_missing())
    @include('partials.affiliate-note')
  @endif
  <div class="uses-body__grid">
    @foreach ($stack as $group)
      {{-- Each group folds open and shut; the first one starts open so the list is not a wall of 37 items. --}}
      <details class="uses-section uses-section--fold" id="uses-{{ \Illuminate\Support\Str::slug($group['title']) }}" @if ($loop->first) open @endif>
        <summary class="uses-section__head">
          <span class="uses-section__icon">{!! \App\mh_svg_icon($group['icon'], 20) !!}</span>
          <h3 id="uses-{{ \Illuminate\Support\Str::slug($group['title']) }}-heading" class="uses-section__title">{{ $group['title'] }}</h3>
          <span class="uses-section__count">{{ number_format_i18n(count($group['items'])) }}</span>
          <span class="uses-section__chev" aria-hidden="true"></span>
        </summary>
        <ul class="uses-list">
          @foreach ($group['items'] as $row)
            @php
              [$name, $desc, $url] = array_pad(array_values($row), 3, null);
              $affiliate = ! empty($row[3]);
            @endphp
            <li class="uses-item">
              <div class="uses-item__name">
                @if ($url)
                  @php $external = \App\mh_is_external_url($url); @endphp
                  <a
                    href="{{ esc_url($url) }}"
                    rel="{{ \App\mh_outbound_rel($affiliate) }}"
                    @if ($external) target="_blank" @endif
                    @if ($affiliate) data-affiliate="true" class="affiliate-link" @endif
                  >{{ $name }}@if ($external)<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>@endif</a>
                  @if ($affiliate)
                    <span class="uses-item__aff">{{ __('Affiliate', 'sage') }}</span>
                  @endif
                @else
                  {{ $name }}
                @endif
              </div>
              <p class="uses-item__desc">{{ $desc }}</p>
            </li>
          @endforeach
        </ul>
      </details>
    @endforeach
  </div>
</div>
