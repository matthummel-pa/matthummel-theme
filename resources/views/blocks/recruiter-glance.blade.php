{{-- matthummel/recruiter-glance: six short facts for hiring managers. Experience counts the years by itself when left empty. --}}
@php
  $gh = \App\Github::fetchUser(\App\mh_github_login());
  $text = static fn (string $key): string => trim((string) ($attributes[$key] ?? ''));
  $experience = $text('experience');
  if ($experience === '') {
    $experience = sprintf(
      /* translators: 1: years of in-house work, 2: public GitHub start year */
      __('%1$d years in-house web · public WordPress work since %2$s', 'sage'),
      \App\mh_years_in_house(),
      trim((string) ($gh['created'] ?? '')) ?: '2025'
    );
  }
  $facts = array_filter([
    [__('Role', 'sage'), $text('role'), 'briefcase'],
    [__('Stack', 'sage'), $text('stack'), 'code'],
    [__('Timezone', 'sage'), $text('timezone'), 'globe'],
    [__('Location', 'sage'), $text('location'), 'map'],
    [__('Experience', 'sage'), $experience, 'calendar'],
    [__('Availability', 'sage'), $text('availability'), 'users'],
  ], static fn (array $fact): bool => $fact[1] !== '');
@endphp
@if ($facts !== [])
  <dl class="{{ trim('mh-data mh-glance h-glance__facts '.($attributes['className'] ?? '')) }}">
    @foreach ($facts as [$label, $value, $icon])
      <div class="h-glance__fact">
        <dt>
          {!! \App\mh_svg_icon($icon, 14) !!}
          {{ $label }}
        </dt>
        <dd>{{ $value }}</dd>
      </div>
    @endforeach
  </dl>
@endif
