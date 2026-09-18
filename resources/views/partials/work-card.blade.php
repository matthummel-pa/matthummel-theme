@php
  $shot = ! empty($p['image'])
    ? (string) $p['image']
    : \App\mh_studio_project_image_url($p);
  if ($shot === '' && ! empty($p['post_id'])) {
    $shot = \App\mh_project_card_image_url((int) $p['post_id']);
  }
  $slug = (string) ($p['slug'] ?? '');
  $title = (string) ($p['title'] ?? '');
  $conceptUrl = (string) ($p['url'] ?? \App\mh_concept_page_url($slug, isset($p['post_id']) ? (int) $p['post_id'] : null));
  $demo = (string) ($p['demo'] ?? '');
  $featured = ! empty($featured);
  $ghost = $featured ? 'btn btn-ghost' : 'btn btn-outline';
  $haystack = strtolower(trim(implode(' ', array_filter([
    $title,
    $p['cat'] ?? '',
    $p['place'] ?? '',
    $p['blurb'] ?? '',
    implode(' ', $p['tech'] ?? []),
    $p['product_type'] ?? '',
  ]))));
@endphp
<article
  class="work-card{{ $featured ? ' work-card--featured' : '' }}"
  id="{{ esc_attr($slug) }}"
  data-work-card
  data-search="{{ esc_attr($haystack) }}"
>
  @if ($shot !== '')
    <a class="work-shot" href="{{ esc_url($conceptUrl) }}" aria-hidden="true" tabindex="-1">
      <img
        src="{{ esc_url($shot) }}"
        alt=""
        width="960"
        height="540"
        loading="{{ $featured ? 'eager' : 'lazy' }}"
        decoding="async"
        @if ($featured)
          fetchpriority="high"
          class="skip-lazy"
          data-no-lazy="1"
        @endif
      >
    </a>
  @endif
  <div class="work-body">
    @if ($featured)
      <p class="eyebrow">{{ __('Featured', 'sage') }}</p>
    @endif
    @include('partials.project-type-row', ['p' => $p])
    <h2><a href="{{ esc_url($conceptUrl) }}">{{ $title }}</a></h2>
    <p>{{ $p['blurb'] }}</p>
    @if (! empty($p['tech']))
      <p class="pill-row">
        @foreach ($p['tech'] as $t)
          <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ $t }}</span>
        @endforeach
      </p>
    @endif
    <div class="work-actions">
      <a class="btn" href="{{ esc_url($conceptUrl) }}">
        {{ \App\field('work_cta_view', \App\mh_projects_listing_default('cta_view')) }}<span class="visually-hidden">{{ sprintf(__(': %s', 'sage'), $title) }}</span>
      </a>
      @if ($demo !== '')
        <a class="{{ $ghost }}" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
          {{ __('Live demo', 'sage') }}<span class="visually-hidden">{{ sprintf(__(' for %s (opens in a new window)', 'sage'), $title) }}</span> <span aria-hidden="true">↗</span>
        </a>
      @endif
    </div>
  </div>
</article>
