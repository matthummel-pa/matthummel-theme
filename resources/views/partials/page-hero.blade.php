@php
  $tag = $tag ?? 'header';
  $extra = trim((string) ($extra ?? ''));
  $inner = trim((string) ($innerClass ?? ''));
  $fallback = trim((string) ($image ?? ''));
  $useScene = ! empty($useScene);
  $heroImage = $useScene
    ? \App\mh_hero_scene_url((int) get_the_ID())
    : \App\mh_hero_background_url(null, $fallback);
  if ($heroImage === '' && $fallback !== '') {
    $heroImage = $fallback;
  }
  $heroAlt = trim((string) ($imageAlt ?? ''));
  $viewport = ! empty($viewport);
@endphp
<{{ $tag }} class="page-header page-header--photo{{ $viewport ? ' page-header--viewport' : '' }}{{ $extra !== '' ? ' '.$extra : '' }}">
  @if ($heroImage !== '')
    <div class="page-header__media" aria-hidden="true">
      <img
        class="page-header__photo"
        src="{!! esc_url($heroImage) !!}"
        alt="{{ $heroAlt }}"
        width="1600"
        height="900"
        decoding="async"
      >
      <div class="page-header__wash"></div>
    </div>
  @endif
  <div class="container wide page-header-inner{{ $inner !== '' ? ' '.$inner : '' }}">
    <div class="page-header__panel">
      {{ $slot }}
    </div>
  </div>
  @include('partials.hero-wave')
</{{ $tag }}>
