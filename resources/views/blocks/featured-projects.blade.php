{{-- matthummel/featured-projects: project cards from the Projects list (same picks as the old Home section). --}}
@php
  $count = min(9, max(1, (int) ($attributes['count'] ?? 3)));
  $work = \App\mh_home_featured_projects($count);
  $total = count(\App\mh_work_page_items());
  $linkText = trim((string) ($attributes['linkText'] ?? ''));
  $showCount = ! empty($attributes['showCount']) && $total > 0;
@endphp
<div class="{{ trim('mh-data mh-featured-projects '.($attributes['className'] ?? '')) }}">
  @if ($showCount || $linkText !== '')
    <div class="mh-data__meta">
      @if ($showCount)
        {{-- translators: %s: number of projects. --}}
        <span class="h-work-count">{{ sprintf(_n('%s project', '%s projects', $total, 'sage'), number_format_i18n($total)) }}</span>
      @endif
      @if ($linkText !== '')
        <a class="h-text-arrow" href="{{ esc_url(\App\mh_work_listing_url()) }}">{{ $linkText }}</a>
      @endif
    </div>
  @endif
  @if ($work !== [])
    <div class="h-project-grid">
      @foreach ($work as $p)
        @include('partials.home-project-card', ['p' => $p])
      @endforeach
    </div>
  @else
    <p class="h-journal__empty">{{ __('Projects are on the way.', 'sage') }}</p>
  @endif
</div>
