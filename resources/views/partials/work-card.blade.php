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
  $helpUrl = (string) ($p['help_url'] ?? (function_exists('\\App\\mh_work_help_url') ? \App\mh_work_help_url($p) : \App\mh_work_contact_url($p)));
  $buyUrl = (string) ($p['buy_url'] ?? '');
  $buyLabel = (string) ($p['buy_label'] ?? \App\field('work_cta_buy', __('Buy theme', 'sage')));
  $priceLabel = (string) ($p['price_label'] ?? '');
  $featured = ! empty($featured);
  $ghost = $featured ? 'btn btn-ghost' : 'btn btn-outline';
  $shareKind = (($p['product_type'] ?? '') === 'plugin')
    ? __('Plugin: %s', 'sage')
    : __('Theme: %s', 'sage');
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
      <img src="{{ esc_url($shot) }}" alt="" width="960" height="540" loading="{{ $featured ? 'eager' : 'lazy' }}" decoding="async">
    </a>
  @endif
  <div class="work-body">
    @include('partials.spec-badge', ['p' => $p])
    @if ($featured)
      <p class="eyebrow">{{ __('Featured', 'sage') }}</p>
    @endif
    <p class="pf-meta">
      {{ $p['cat'] }} · {{ $p['place'] }}
      @if ($priceLabel !== '')
        <span aria-hidden="true"> · </span>
        <span class="work-card-price">{{ $priceLabel }}</span>
      @endif
    </p>
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
        {{ \App\field('work_cta_view', __('View details', 'sage')) }}<span class="visually-hidden">{{ sprintf(__(': %s', 'sage'), $title) }}</span>
      </a>
      <a class="{{ $ghost }}" href="{{ esc_url($helpUrl) }}">
        {!! \App\mh_svg_icon('mail', 14) !!}
        {{ \App\field('work_cta_help', __('Get help', 'sage')) }}
        <span class="visually-hidden">{{ sprintf(__(': %s', 'sage'), $title) }}</span>
      </a>
      @if ($buyUrl !== '')
        <a class="{{ $ghost }}" href="{{ esc_url($buyUrl) }}">
          {!! \App\mh_svg_icon('cart', 14) !!}
          {{ $buyLabel }}
          <span class="visually-hidden">{{ sprintf(__(': %s', 'sage'), $title) }}</span>
        </a>
      @endif
      @if ($demo !== '')
        <a class="{{ $ghost }}" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
          {{ __('Live demo', 'sage') }}<span class="visually-hidden">{{ sprintf(__(' for %s (opens in a new window)', 'sage'), $title) }}</span> <span aria-hidden="true">↗</span>
        </a>
      @endif
      <button
        type="button"
        class="{{ $ghost }}"
        data-share-project
        data-share-url="{{ esc_url($shareUrl) }}"
        data-share-title="{{ esc_attr($title) }}"
        data-share-text="{{ esc_attr(sprintf($shareKind, $title)) }}"
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
