@php
  $pills = $pills ?? [];
  $pillClass = $pillClass ?? 'h-page-nav__pill';
  $listClass = $listClass ?? 'h-page-nav__pills';
  $scrollerId = $scrollerId ?? 'on-this-page-pills';
@endphp
<div class="h-page-nav__track">
  <button
    type="button"
    class="h-page-nav__arrow h-page-nav__arrow--prev"
    data-page-nav-prev
    aria-controls="{{ $scrollerId }}"
    aria-label="{{ __('Previous sections', 'sage') }}"
    hidden
  >
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
      <path d="M15 6 9 12l6 6"/>
    </svg>
  </button>
  <div id="{{ $scrollerId }}" class="{{ $listClass }}" role="list" data-page-nav-scroller>
    @foreach ($pills as [$id, $label])
      <a class="{{ $pillClass }}" role="listitem" href="#{{ $id }}">{{ $label }}</a>
    @endforeach
  </div>
  <button
    type="button"
    class="h-page-nav__arrow h-page-nav__arrow--next"
    data-page-nav-next
    aria-controls="{{ $scrollerId }}"
    aria-label="{{ __('Next sections', 'sage') }}"
    hidden
  >
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
      <path d="M9 6l6 6-6 6"/>
    </svg>
  </button>
</div>
