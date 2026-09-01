@php
  $badge = $label ?? \App\mh_spec_badge_label($p ?? []);
  $onDark = ! empty($onDark);
@endphp
@if ($badge !== '')
  <span class="spec-badge{{ $onDark ? ' spec-badge--on-dark' : '' }}">{{ $badge }}</span>
@endif
