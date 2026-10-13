{{-- matthummel/contact-facts: reply time, availability, location and best fit. Availability prints only while GitHub says hireable. --}}
@php
  $gh = \App\Github::fetchUser(\App\mh_github_login());
  $text = static fn (string $key): string => trim((string) ($attributes[$key] ?? ''));
  $reply = $text('reply') ?: sprintf(
    /* translators: %s: reply time, e.g. "Within one business day (ET)" */
    __('%s. I read every note.', 'sage'),
    ucfirst(\App\mh_reply_sla('phrase'))
  );
@endphp
<div class="{{ trim('mh-data mh-contact-facts contact-info-card '.($attributes['className'] ?? '')) }}">
  <ul class="contact-info-list">
    <li>
      <span class="contact-info-icon">{!! \App\mh_svg_icon('calendar', 16) !!}</span>
      <div>
        <strong>{{ __('Reply time', 'sage') }}</strong>
        <p>{{ $reply }}</p>
      </div>
    </li>
    @if (\App\mh_is_hireable($gh))
      <li>
        <span class="contact-info-icon">@include('partials.avail-mark', ['gh' => $gh])</span>
        <div>
          <strong>{{ \App\mh_availability_label($gh, __('Open for work', 'sage')) }}</strong>
          @if ($text('availability') !== '')
            <p>{{ $text('availability') }}</p>
          @endif
        </div>
      </li>
    @endif
    @if ($text('location') !== '')
      <li>
        <span class="contact-info-icon">{!! \App\mh_svg_icon('map', 16) !!}</span>
        <div>
          <strong>{{ __('Location', 'sage') }}</strong>
          <p>{{ $text('location') }}</p>
        </div>
      </li>
    @endif
    @if ($text('bestFit') !== '')
      <li>
        <span class="contact-info-icon">{!! \App\mh_svg_icon('code', 16) !!}</span>
        <div>
          <strong>{{ __('Best fit', 'sage') }}</strong>
          <p>{{ $text('bestFit') }}</p>
        </div>
      </li>
    @endif
  </ul>
</div>
