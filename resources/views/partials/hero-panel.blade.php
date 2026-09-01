@php
  $chrome = (string) ($chrome ?? '');
  $icon = (string) ($icon ?? 'code');
  $title = (string) ($title ?? '');
  $meta = (string) ($meta ?? '');
  $stats = is_array($stats ?? null) ? $stats : [];
  $link = is_array($link ?? null) ? $link : null;
  $status = is_array($status ?? null) ? $status : null;
  $profileSize = (int) ($profileSize ?? 0);
  $profileCaption = (string) ($profileCaption ?? '');
  $hasHead = $title !== '' || $meta !== '' || $status !== null;
@endphp
<div class="h-hero-illu">
  <span class="h-hero-illu__glow" aria-hidden="true"></span>
  <span class="h-hero-illu__orb h-hero-illu__orb--a" aria-hidden="true"></span>
  <span class="h-hero-illu__orb h-hero-illu__orb--b" aria-hidden="true"></span>

  <div class="h-hero-illu__card">
    @if ($chrome !== '')
      <div class="h-hero-illu__chrome" aria-hidden="true">
        <span class="h-hero-illu__dot"></span>
        <span class="h-hero-illu__dot"></span>
        <span class="h-hero-illu__dot"></span>
        <span class="h-hero-illu__url">{{ $chrome }}</span>
      </div>
    @endif

    @if ($profileSize > 0)
      <div class="h-hero-illu__profile">
        @include('partials.profile-photo', [
          'size'  => $profileSize,
          'class' => 'h-hero-illu__photo',
          'eager' => true,
        ])
        @if ($profileCaption !== '')
          <p class="h-hero-illu__profile-cap">{{ $profileCaption }}</p>
        @endif
      </div>
    @endif

    @if ($hasHead)
      <div class="h-hero-illu__head">
        <div class="h-hero-illu__identity">
          {!! \App\mh_svg_icon($icon, 18) !!}
          <div>
            @if ($title !== '')
              <p class="h-hero-illu__handle">{{ $title }}</p>
            @endif
            @if ($meta !== '')
              <p class="h-hero-illu__meta">{{ $meta }}</p>
            @endif
          </div>
        </div>
        @if ($status !== null && ! empty($status['label']))
          <span class="h-hero-illu__status">
            @if (! empty($status['gh']))
              @include('partials.avail-mark', ['gh' => $status['gh']])
            @endif
            {{ $status['label'] }}
          </span>
        @endif
      </div>
    @endif

    @if ($stats !== [])
      <dl class="h-hero-illu__stats">
        @foreach ($stats as $stat)
          <div class="h-hero-illu__stat">
            <dt>
              @if (! empty($stat['href']))
                <a href="{{ esc_url($stat['href']) }}" @if (! empty($stat['external'])) rel="noopener" target="_blank" @else rel="me" @endif>
                  {{ $stat['value'] }}
                  @if (! empty($stat['external']))
                    <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
                  @endif
                </a>
              @else
                {{ $stat['value'] }}
              @endif
            </dt>
            <dd>{{ $stat['label'] }}</dd>
          </div>
        @endforeach
      </dl>
    @endif

    @if ($link !== null && ! empty($link['href']) && ! empty($link['label']))
      <a
        class="h-hero-illu__link"
        href="{{ esc_url($link['href']) }}"
        @if (! empty($link['external'])) rel="noopener" target="_blank" @endif
      >
        {{ $link['label'] }}
        <span aria-hidden="true">→</span>
        @if (! empty($link['external']))
          <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
        @endif
      </a>
    @endif
  </div>
</div>
