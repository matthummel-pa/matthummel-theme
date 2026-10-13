{{-- matthummel/social-links: the site's profile links with labels (Settings → Social links). "Show notes" adds a short line under each. --}}
@php
  $showNotes = ! empty($attributes['showNotes']);
@endphp
<div class="{{ trim('mh-data mh-social-links '.($showNotes ? '' : 'about-elsewhere__links ').($attributes['className'] ?? '')) }}">
  @if ($showNotes)
    @include('partials.social', ['labeled' => true, 'cards' => true, 'links' => \App\mh_contact_else_links()])
  @else
    @include('partials.social', ['labeled' => true])
  @endif
</div>
