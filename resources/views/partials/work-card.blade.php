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
  $techKey = '|'.implode('|', array_map(static fn ($t) => strtolower(trim((string) $t)), (array) ($p['tech'] ?? []))).'|';
  $publishedTs = ! empty($p['published']) ? strtotime((string) $p['published']) : false;
@endphp
<article
  class="work-card{{ $featured ? ' work-card--featured' : '' }}"
  id="{{ esc_attr($slug) }}"
  data-work-card
  data-cat="{{ esc_attr((string) ($p['cat'] ?? '')) }}"
  data-search="{{ esc_attr($haystack) }}"
  data-tech="{{ esc_attr($techKey) }}"
  data-title="{{ esc_attr(strtolower($title)) }}"
  data-date="{{ esc_attr($publishedTs ? gmdate('c', $publishedTs) : '') }}"
  data-order="{{ (int) ($order ?? 0) }}"
  @if (! empty($isHidden)) hidden @endif
>
  @if ($shot !== '')
    {{-- The link is hidden from assistive tech (the title link follows); the alt is for image search. --}}
    <a class="work-shot" href="{{ esc_url($conceptUrl) }}" aria-hidden="true" tabindex="-1">
      <img
        src="{{ esc_url($shot) }}"
        alt="{{ sprintf(__('%s demo screenshot', 'sage'), $title) }}"
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
    {{-- H3: cards always sit under a section H2 (project browser, More projects). --}}
    <h3><a href="{{ esc_url($conceptUrl) }}">{{ $title }}</a></h3>
    <p class="work-blurb">{{ $p['blurb'] }}</p>
    @if (! empty($p['tech']))
      @php
        $techAll = array_values(array_filter(array_map('strval', (array) $p['tech'])));
        $techShown = array_slice($techAll, 0, 4);
        $techRest = count($techAll) - count($techShown);
      @endphp
      <p class="pill-row">
        @foreach ($techShown as $t)
          <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ $t }}</span>
        @endforeach
        @if ($techRest > 0)
          {{-- translators: %s is a comma-separated list of tool names. --}}
          <span class="pill pill--more" title="{{ esc_attr(implode(', ', array_slice($techAll, 4))) }}">+{{ $techRest }}<span class="visually-hidden"> {{ sprintf(_n('more tool: %s', 'more tools: %s', $techRest, 'sage'), implode(', ', array_slice($techAll, 4))) }}</span></span>
        @endif
      </p>
    @endif
    @if ($publishedTs)
      <p class="work-meta">
        {{-- translators: %s is a <time> element holding a month and year, e.g. Oct 2026. --}}
        {!! wp_kses(sprintf(esc_html__('Added %s', 'sage'), '<time datetime="'.esc_attr(gmdate('c', $publishedTs)).'">'.esc_html(wp_date('M Y', $publishedTs)).'</time>'), ['time' => ['datetime' => true]]) !!}
      </p>
    @endif
    <div class="work-actions">
      <a class="shop-link shop-link--forward" href="{{ esc_url($conceptUrl) }}">
        {{ \App\field('work_cta_view', \App\mh_projects_listing_default('cta_view')) }}<span class="visually-hidden">{{ sprintf(__(': %s', 'sage'), $title) }}</span>
        <span class="shop-link__icon" aria-hidden="true">&rarr;</span>
      </a>
      @if ($demo !== '')
        <a class="shop-link" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
          {{ __('Live demo', 'sage') }}<span class="visually-hidden">{{ sprintf(__(' for %s (opens in a new window)', 'sage'), $title) }}</span>
          <span class="shop-link__icon" aria-hidden="true">&#8599;</span>
        </a>
      @endif
    </div>
  </div>
</article>
