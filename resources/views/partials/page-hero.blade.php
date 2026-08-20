@php
  $tag = $tag ?? 'header';
  $extra = trim((string) ($extra ?? ''));
  $inner = trim((string) ($innerClass ?? ''));
@endphp
<{{ $tag }} class="page-header{{ $extra !== '' ? ' '.$extra : '' }}">
  @include('partials.hero-graphic', ['still' => true])
  <div class="container wide page-header-inner{{ $inner !== '' ? ' '.$inner : '' }}">
    {{ $slot }}
  </div>
</{{ $tag }}>
