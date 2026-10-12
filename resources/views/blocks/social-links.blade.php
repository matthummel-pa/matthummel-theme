{{-- matthummel/social-links: the site's profile links with labels (Settings → Social links). --}}
<div class="{{ trim('mh-data mh-social-links about-elsewhere__links '.($attributes['className'] ?? '')) }}">
  @include('partials.social', ['labeled' => true])
</div>
