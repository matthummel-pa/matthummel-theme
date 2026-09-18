{{-- Type pill next to category / place pills. No plain-text cat line. --}}
@php
  $project = $p ?? [];
  $typeLabel = \App\mh_spec_badge_label($project);
  $catLabel = trim((string) ($project['cat'] ?? $cat ?? ''));
  $placeLabel = trim((string) ($project['place'] ?? $place ?? ''));
  if ($typeLabel !== '' && $catLabel !== '') {
    $typeNorm = strtolower($typeLabel);
    $catNorm = strtolower($catLabel);
    if ($catNorm === $typeNorm || $catNorm === $typeNorm.'s' || rtrim($catNorm, 's') === rtrim($typeNorm, 's')) {
      $catLabel = '';
    }
  }
@endphp
@if ($typeLabel !== '' || $catLabel !== '' || $placeLabel !== '')
  <p class="project-type-row">
    @if ($typeLabel !== '')
      <span class="spec-badge">{{ $typeLabel }}</span>
    @endif
    @if ($catLabel !== '')
      <span class="project-cat-pill">{{ $catLabel }}</span>
    @endif
    @if ($placeLabel !== '')
      <span class="project-place-pill">{!! \App\mh_svg_icon('map', 12) !!} {{ $placeLabel }}</span>
    @endif
  </p>
@endif
