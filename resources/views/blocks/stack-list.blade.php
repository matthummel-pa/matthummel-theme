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
      <section class="uses-section" id="uses-{{ \Illuminate\Support\Str::slug($group['title']) }}" aria-labelledby="uses-{{ \Illuminate\Support\Str::slug($group['title']) }}-heading">
        <div class="uses-section__head">
          <div class="uses-section__icon">{!! \App\mh_svg_icon($group['icon'], 20) !!}</div>
          <h3 id="uses-{{ \Illuminate\Support\Str::slug($group['title']) }}-heading" class="uses-section__title">{{ $group['title'] }}</h3>
        </div>
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
      </section>
    @endforeach
  </div>
</div>
