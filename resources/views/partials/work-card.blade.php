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
    <a class="work-shot" href="{{ esc_url($conceptUrl) }}" aria-hidden="true" tabindex="-1">
      <img src="{{ esc_url($shot) }}" alt="" width="960" height="540" loading="{{ $featured ? 'eager' : 'lazy' }}" decoding="async">
    </a>
  @endif
  <div class="work-body">
    @if ($featured)
      <p class="eyebrow">{{ __('Featured', 'sage') }}</p>
    @endif
    <p class="pf-meta">{{ $p['cat'] }} · {{ $p['place'] }}</p>
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
      <a class="{{ $featured ? 'btn btn-ghost' : 'btn btn-outline' }}" href="{{ esc_url($conceptUrl) }}">
        {{ \App\field('work_cta_view', __('View project', 'sage')) }}<span class="visually-hidden">{{ sprintf(__(': %s', 'sage'), $title) }}</span>
      </a>
      @if ($demo !== '')
        <a class="{{ $featured ? 'btn btn-ghost' : 'btn btn-outline' }}" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
          {{ __('Live demo', 'sage') }}<span class="visually-hidden">{{ sprintf(__(' for %s (opens in a new window)', 'sage'), $title) }}</span> <span aria-hidden="true">↗</span>
        </a>
      @endif
      <a class="btn" href="{{ esc_url($useUrl) }}">
        {{ \App\field('work_cta_use', __('Use this project', 'sage')) }}<span class="visually-hidden">{{ sprintf(__(': %s', 'sage'), $title) }}</span>
      </a>
      <button
        type="button"
        class="{{ $featured ? 'btn btn-ghost' : 'btn btn-outline' }}"
        data-share-project
        data-share-url="{{ esc_url($shareUrl) }}"
        data-share-title="{{ esc_attr($title) }}"
        data-share-text="{{ esc_attr(sprintf(__('Example project: %s', 'sage'), $title)) }}"
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
