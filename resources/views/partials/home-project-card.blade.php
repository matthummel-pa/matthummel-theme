{{-- Home featured project card. Expects $p work/project card array. --}}
@php
  $title = (string) ($p['title'] ?? '');
  $url = esc_url($p['url'] ?? \App\mh_work_listing_url());
  $image = (string) ($p['image'] ?? '');
  $blurb = trim((string) ($p['blurb'] ?? $p['summary'] ?? ''));
  $cat = (string) ($p['cat'] ?? '');
  $demo = (string) ($p['demo'] ?? '');
@endphp
<article class="h-project-card">
  <a class="h-project-card__media" href="{{ $url }}" tabindex="-1" aria-hidden="true">
    @if ($image !== '')
      <img
        src="{{ esc_url($image) }}"
        alt=""
        width="640"
        height="400"
        loading="lazy"
        decoding="async"
      >
    @else
      <span class="h-project-card__fallback">{{ $title }}</span>
    @endif
  </a>
  <div class="h-project-card__body">
    @if ($cat !== '')
      <p class="h-project-card__cat">{{ $cat }}</p>
    @endif
    <h3 class="h-project-card__title">
      <a href="{{ $url }}">{{ $title }}</a>
    </h3>
    @if ($blurb !== '')
      <p class="h-project-card__blurb">{{ wp_trim_words($blurb, 28, '…') }}</p>
    @endif
    <p class="h-project-card__links">
      <a class="h-text-arrow" href="{{ $url }}">{{ __('Open project', 'sage') }} <span aria-hidden="true">→</span></a>
      @if ($demo !== '')
        <a class="h-text-arrow h-text-arrow--quiet" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
          {{ __('Live demo', 'sage') }} <span aria-hidden="true">↗</span>
        </a>
      @endif
    </p>
  </div>
</article>
