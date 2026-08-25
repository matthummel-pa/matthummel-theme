{{-- GitHub status emoji when set; otherwise the green pulse dot. --}}
@php
  $gh = $gh ?? \App\Github::fetchUser(\App\mh_github_login());
  $emoji = \App\mh_availability_emoji($gh);
@endphp
@if ($emoji !== '')
  <span class="mh-avail__emoji" aria-hidden="true">{{ $emoji }}</span>
@else
  <span class="h-badge__dot" aria-hidden="true"></span>
@endif
