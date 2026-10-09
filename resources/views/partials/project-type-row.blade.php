{{-- Type pill next to industry / place. No plain-text cat · place line. --}}
@php
  $row = \App\mh_project_type_row_labels($p ?? []);
  $typeLabel = (string) ($row['type'] ?? '');
  $catLabel = (string) ($row['cat'] ?? '');
  $placeLabel = (string) ($row['place'] ?? '');
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
