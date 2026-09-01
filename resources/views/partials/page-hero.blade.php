@php
  $tag = $tag ?? 'header';
  $extra = trim((string) ($extra ?? ''));
  $inner = trim((string) ($innerClass ?? ''));
  $split = ! empty($split);
  $asideLabel = trim((string) ($asideLabel ?? ''));
@endphp
<{{ $tag }} class="page-header{{ $split ? ' page-header--split' : '' }}{{ $extra !== '' ? ' '.$extra : '' }}">
  <div class="container wide page-header-inner{{ $inner !== '' ? ' '.$inner : '' }}{{ $split ? ' page-header-inner--split' : '' }}">
    @if ($split)
      <div class="page-header-split__copy">
        {{ $slot }}
      </div>
      @if (! empty($aside))
        <aside class="page-header-split__aside" @if ($asideLabel !== '') aria-label="{{ $asideLabel }}" @endif>
          {{ $aside }}
        </aside>
      @endif
    @else
      {{ $slot }}
    @endif
  </div>
</{{ $tag }}>
