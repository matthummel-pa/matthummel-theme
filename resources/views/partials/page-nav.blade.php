{{-- Sticky On this page pills + overflow arrows. Same chrome on every page. --}}
@php
  $pills = $pills ?? [];
  $clean = [];
  foreach ($pills as $row) {
      if (! is_array($row) || count($row) < 2) {
          continue;
      }
      $id = sanitize_html_class((string) $row[0]);
      $label = trim((string) $row[1]);
      if ($id === '' || $label === '') {
          continue;
      }
      $clean[] = [$id, $label];
  }
  $min = (int) ($min ?? 2);
  $innerClass = ! empty($nested) ? 'h-page-nav__inner' : 'container wide h-page-nav__inner';
@endphp
@if (count($clean) >= $min)
<nav class="h-page-nav" data-section-nav aria-label="{{ __('On this page', 'sage') }}">
  <div class="{{ $innerClass }}">
    <p class="h-page-nav__label">{{ __('On this page', 'sage') }}</p>
    @include('partials.page-nav-track', [
      'pills' => $clean,
      'pillClass' => 'h-page-nav__pill',
      'listClass' => 'h-page-nav__pills',
      'scrollerId' => $scrollerId ?? 'on-this-page-pills',
    ])
  </div>
</nav>
@endif
