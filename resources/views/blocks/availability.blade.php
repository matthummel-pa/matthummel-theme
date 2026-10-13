{{-- matthummel/availability: one line, printed only while the GitHub profile is marked hireable. --}}
@php
  $gh = \App\Github::fetchUser(\App\mh_github_login());
  $fallback = trim((string) ($attributes['label'] ?? '')) ?: __('Available for hire', 'sage');
@endphp
@if (\App\mh_is_hireable($gh))
  <p class="{{ trim('about-aside-avail mh-availability '.($attributes['className'] ?? '')) }}">
    @include('partials.avail-mark', ['gh' => $gh])
    {{ \App\mh_availability_label($gh, $fallback) }}
  </p>
@endif
