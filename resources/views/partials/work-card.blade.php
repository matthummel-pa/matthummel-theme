@php
  $shot = \App\mh_studio_project_image_url($p);
  $concept = (string) ($p['concept'] ?? '');
  $slug = (string) ($p['slug'] ?? '');
  $title = (string) ($p['title'] ?? '');
  $shareUrl = \App\mh_work_permalink($slug, $pageUrl ?? null);
  $useUrl = \App\mh_work_contact_url($p);
  $featured = ! empty($featured);
  $haystack = strtolower(trim(implode(' ', array_filter([
    $title,
    $p['cat'] ?? '',
    $p['place'] ?? '',
    $p['blurb'] ?? '',
    implode(' ', $p['tech'] ?? []),
  ]))));
@endphp
<article
  class="work-card{{ $featured ? ' work-card--featured' : '' }}"
  id="{{ esc_attr($slug) }}"
  data-work-card
  data-search="{{ esc_attr($haystack) }}"
>
  @if ($shot !== '')
    <div class="work-shot">
      <img src="{{ esc_url($shot) }}" alt="{{ esc_attr(sprintf(__('Screenshot of the %s concept', 'sage'), $title)) }}" width="960" height="540" loading="{{ $featured ? 'eager' : 'lazy' }}" decoding="async">
    </div>
  @endif
  <div class="work-body">
    @if ($featured)
      <p class="eyebrow">{{ __('Featured', 'sage') }}</p>
    @endif
    <p class="pf-meta">{{ $p['cat'] }} · {{ $p['place'] }}</p>
    <h2>{{ $title }}</h2>
    <p>{{ $p['blurb'] }}</p>
    @if (! empty($p['tech']))
      <p class="pill-row">
        @foreach ($p['tech'] as $t)
          <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ $t }}</span>
        @endforeach
      </p>
    @endif
    <div class="work-actions">
      @if ($concept !== '')
        <a class="btn btn-outline" href="{{ esc_url($concept) }}" rel="noopener" target="_blank">
          {{ \App\field('work_cta_view', __('View concept', 'sage')) }}<span class="visually-hidden">{{ sprintf(__(': %s (opens in a new window)', 'sage'), $title) }}</span>
        </a>
      @endif
      <a class="btn" href="{{ esc_url($useUrl) }}">
        {{ \App\field('work_cta_use', __('Use this concept', 'sage')) }}<span class="visually-hidden">{{ sprintf(__(': %s', 'sage'), $title) }}</span>
      </a>
      <button
        type="button"
        class="btn btn-outline"
        data-share-project
        data-share-url="{{ esc_url($shareUrl) }}"
        data-share-title="{{ esc_attr($title) }}"
        data-share-text="{{ esc_attr(sprintf(__('Example site concept: %s', 'sage'), $title)) }}"
      >
        {{ __('Share', 'sage') }}<span class="visually-hidden">{{ sprintf(__(' %s', 'sage'), $title) }}</span>
      </button>
      <button
        type="button"
        class="write-tool-link"
        data-copy-url="{{ esc_url($shareUrl) }}"
      >
        {{ __('Copy link', 'sage') }}<span class="visually-hidden">{{ sprintf(__(' to %s', 'sage'), $title) }}</span>
      </button>
    </div>
  </div>
</article>
