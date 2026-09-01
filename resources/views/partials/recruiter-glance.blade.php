@php
  $glance = $glance ?? \App\mh_recruiter_glance();
  $facts = [
    ['Role', $glance['role'], 'briefcase'],
    ['Stack', $glance['stack'], 'code'],
    ['Timezone', $glance['timezone'], 'globe'],
    ['Location', $glance['location'], 'map'],
    ['Experience', $glance['experience'], 'calendar'],
    ['Availability', $glance['availability'], 'users'],
  ];
@endphp
<section class="h-glance" id="glance" aria-labelledby="h-glance-heading">
  <div class="container wide">
    <div class="h-glance__card">
      <header class="h-glance__head">
        <p class="h-section-label">{{ __('At a glance', 'sage') }}</p>
        <h2 id="h-glance-heading" class="h-glance__title">{{ __('For hiring managers.', 'sage') }}</h2>
        <p class="h-glance__note">{{ $glance['note'] }}</p>
      </header>

      <dl class="h-glance__facts">
        @foreach ($facts as [$label, $value, $icon])
          <div class="h-glance__fact">
            <dt>
              {!! \App\mh_svg_icon($icon, 14) !!}
              {{ $label }}
            </dt>
            <dd>{{ $value }}</dd>
          </div>
        @endforeach
      </dl>

      @if (($glance['employers'] ?? '') !== '' || ($glance['power'] ?? '') !== '' || ($glance['range'] ?? '') !== '')
        <div class="h-glance__proof">
          @if (($glance['employers'] ?? '') !== '')
            <p>{!! $glance['employers'] !!}</p>
          @endif
          @if (($glance['power'] ?? '') !== '')
            <p>{!! $glance['power'] !!}</p>
          @endif
          @if (($glance['range'] ?? '') !== '')
            <p>{{ $glance['range'] }}</p>
          @endif
        </div>
      @endif

      <div class="h-glance__links">
        @foreach ($glance['links'] as $i => $link)
          @if ($i === 0)
            <a class="btn" href="{{ esc_url($link['href']) }}" @if (! empty($link['external'])) rel="me noopener" target="_blank" @endif>
              {!! \App\mh_svg_icon($link['icon'], 16) !!}
              {{ $link['label'] }}
              @if (! empty($link['external']))
                <span class="visually-hidden">{{ __(' (opens in a new window)', 'sage') }}</span>
              @endif
            </a>
          @else
            <a class="h-text-arrow" href="{{ esc_url($link['href']) }}" @if (! empty($link['external'])) rel="me noopener" target="_blank" @endif>
              {{ $link['label'] }}
              <span aria-hidden="true">→</span>
              @if (! empty($link['external']))
                <span class="visually-hidden">{{ __(' (opens in a new window)', 'sage') }}</span>
              @endif
            </a>
          @endif
        @endforeach
      </div>

      @if ($glance['nda'] !== '')
        <p class="h-glance__nda">{{ $glance['nda'] }}</p>
      @endif
    </div>
  </div>
</section>
