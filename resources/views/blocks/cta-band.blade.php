{{-- matthummel/cta-band: buttons go to the action column, everything else to the copy column. --}}
@php
  $eyebrow = trim((string) ($attributes['eyebrow'] ?? ''));
  $note = trim((string) ($attributes['note'] ?? ''));
  $gh = ! empty($attributes['showAvailability']) ? \App\Github::fetchUser(\App\mh_github_login()) : [];
  $copy = '';
  $actions = '';
  foreach ($block->inner_blocks as $inner) {
    if ($inner->name === 'core/buttons') {
      $actions .= $inner->render();
    } else {
      $copy .= $inner->render();
    }
  }
@endphp
<section class="{{ trim('cta-band mh-block-cta '.($attributes['className'] ?? '')) }}" data-reveal>
  <div class="container wide cta-band-inner">
    <div class="cta-band__copy">
      @if ($eyebrow !== '' || ($gh && \App\mh_is_hireable($gh)))
        <p class="eyebrow eyebrow--on-dark">
          @if ($gh && \App\mh_is_hireable($gh))
            @include('partials.avail-mark', ['gh' => $gh])
          @endif
          {{ $eyebrow !== '' ? $eyebrow : __('Available now', 'sage') }}
        </p>
      @endif
      {!! $copy !!}
    </div>
    <div class="cta-band__actions">
      {!! $actions !!}
      @if ($note !== '')
        <p class="cta-band__note">{{ $note }}</p>
      @endif
    </div>
  </div>
</section>
