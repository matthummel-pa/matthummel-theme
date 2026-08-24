@php
  $tag = $tag ?? 'header';
  $extra = trim((string) ($extra ?? ''));
  $inner = trim((string) ($innerClass ?? ''));
@endphp
<{{ $tag }} class="page-header{{ $extra !== '' ? ' '.$extra : '' }}">
  <div class="container wide page-header-inner{{ $inner !== '' ? ' '.$inner : '' }}">
    {{ $slot }}
  </div>
</{{ $tag }}>
