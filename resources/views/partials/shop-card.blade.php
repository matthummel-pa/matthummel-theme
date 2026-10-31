{{-- One product in the shop listing. Expects $item from \App\mh_shop_items(). Builds on the work-card styles. The feature chips, checklist, quality tiles and version line live in a details panel that an info button opens (hover, focus or click; js: card-details.js). --}}
@php
  $status = (string) $item['status'];
  $isSoon = $status === 'soon';
  $typeLabel = \App\mh_product_types()[$item['type']]['singular'] ?? '';
  $titleId = $item['slug'].'-title';
  $detailsId = $item['slug'].'-details';
  $hasDetails = ! $isSoon && ($item['facts'] !== [] || $item['points'] !== [] || $item['quality'] !== [] || $item['version'] !== '' || $item['works_with'] !== '');
@endphp
<article class="work-card shop-card shop-card--{{ esc_attr($status) }} shop-card--compact" id="{{ esc_attr($item['slug']) }}" aria-labelledby="{{ esc_attr($titleId) }}">
  @if ($item['image'] !== '')
    {{-- The image link is hidden from assistive tech: the title link below goes to the same page. --}}
    <a class="work-shot" href="{{ esc_url($item['url']) }}" aria-hidden="true" tabindex="-1">
      <img
        src="{{ esc_url($item['image']) }}"
        alt="{{ sprintf(__('%s screenshot', 'sage'), $item['title']) }}"
        width="960"
        height="540"
        loading="lazy"
        decoding="async"
      >
    </a>
  @endif
  @if ($hasDetails)
    {{-- Info button on the screenshot corner; the panel covers the card. Opens on hover, focus or click (card-details.js). --}}
    <div class="shop-card__more" data-card-more>
      <button type="button" class="shop-card__more-btn" aria-expanded="false" aria-controls="{{ esc_attr($detailsId) }}">
        {!! \App\mh_svg_icon('info', 22) !!}
        <span class="visually-hidden">{{ sprintf(__('More details about %s', 'sage'), $item['title']) }}</span>
      </button>
      <div class="shop-card__details" id="{{ esc_attr($detailsId) }}" role="group" aria-label="{{ esc_attr(sprintf(__('Details for %s', 'sage'), $item['title'])) }}">
        <p class="shop-card__details-title">{{ sprintf(__('More about %s', 'sage'), $item['title']) }}</p>
      @if (! $isSoon && $item['facts'] !== [])
        <ul class="shop-card__facts" aria-label="{{ __('Highlights', 'sage') }}">
          @foreach ($item['facts'] as $fact)
            <li>{{ $fact }}</li>
          @endforeach
        </ul>
      @endif
      @if (! $isSoon && $item['points'] !== [])
        <ul class="shop-card__points">
          @foreach ($item['points'] as $point)
            <li>{{ $point }}</li>
          @endforeach
        </ul>
      @endif
      @if (! $isSoon && $item['quality'] !== [])
        <dl class="shop-card__quality" aria-label="{{ __('Quality checks', 'sage') }}">
          @foreach ($item['quality'] as [$statValue, $statLabel])
            <div>
              <dt>{{ $statValue }}</dt>
              <dd>{{ $statLabel }}</dd>
            </div>
          @endforeach
        </dl>
      @endif

      @if (! $isSoon && ($item['version'] !== '' || $item['works_with'] !== ''))
        <p class="shop-card__meta">
          @if ($item['version'] !== '')
            {{ sprintf(__('Version %s', 'sage'), $item['version']) }}
          @endif
          @if ($item['version'] !== '' && $item['works_with'] !== '')
            <span aria-hidden="true">·</span>
          @endif
          @if ($item['works_with'] !== '')
            {{ $item['works_with'] }}
          @endif
        </p>
      @endif
        <a class="shop-link shop-card__details-link" href="{{ esc_url($item['url']) }}">
          {{ __('See the full details', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('for %s', 'sage'), $item['title']) }}</span>
          <span class="shop-link__icon" aria-hidden="true">→</span>
        </a>
      </div>
    </div>
  @endif
  <div class="work-body">
    <h3 id="{{ esc_attr($titleId) }}"><a href="{{ esc_url($item['url']) }}">{{ $item['title'] }}</a></h3>
    <p class="project-type-row">
      <span class="spec-badge">{{ $typeLabel }}</span>
      @if ($isSoon)
        <span class="spec-badge">{{ __('Coming soon', 'sage') }}</span>
      @endif
    </p>
    @if ($item['place'] !== '')
      <p class="shop-card__for"><span>{{ __('Best for', 'sage') }}</span> {{ $item['place'] }}</p>
    @endif
    @if ($item['blurb'] !== '')
      <p class="work-blurb">{{ $item['blurb'] }}</p>
    @endif

    @if (! $isSoon && $item['price'] !== '')
      <p class="shop-card__price"><strong>{{ $item['price'] }}</strong></p>
    @endif
    {{-- The button gets its own row; the plain links sit on one line below it. --}}
    <div class="shop-card__cta">
      @if ($status === 'buy')
        <a class="btn shop-card__button" href="{{ esc_url($item['url']) }}#buy">
          {!! \App\mh_svg_icon('cart', 15) !!}
          {{ __('Choose a plan', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('for %s', 'sage'), $item['title']) }}</span>
        </a>
      @elseif ($status === 'free')
        <a class="btn shop-card__button" href="{{ esc_url($item['free_url']) }}" rel="noopener">
          {!! \App\mh_svg_icon('download', 15) !!}
          {{ __('Download free', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('%s (zip)', 'sage'), $item['title']) }}</span>
        </a>
      @elseif ($isSoon)
        <a class="btn btn-outline shop-card__button" href="{{ esc_url(home_url('/get-updates/')) }}">
          {!! \App\mh_svg_icon('mail', 15) !!}
          {{ __('Get updates', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('to hear when %s is ready', 'sage'), $item['title']) }}</span>
        </a>
      @else
        <a class="btn btn-outline shop-card__button" href="{{ esc_url($item['url']) }}">
          {{ __('View details', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('for %s', 'sage'), $item['title']) }}</span>
        </a>
      @endif

      @if ($item['demo'] !== '' || (! $isSoon && $item['docs_url'] !== '' && $item['docs_label'] !== ''))
        <p class="shop-card__links">
          @if ($item['demo'] !== '')
            <a class="shop-link" href="{{ esc_url($item['demo']) }}" rel="noopener" target="_blank">
              {{ $item['type'] === 'plugin' ? __('Docs and demo', 'sage') : __('Live demo', 'sage') }}<span class="visually-hidden">{{ sprintf(__(' for %s (opens in a new window)', 'sage'), $item['title']) }}</span>
              <span class="shop-link__icon" aria-hidden="true">↗</span>
            </a>
          @endif
          @if (! $isSoon && $item['docs_url'] !== '' && $item['docs_label'] !== '')
            <a class="shop-link" href="{{ esc_url($item['docs_url']) }}" rel="noopener" target="_blank">
              {{ $item['docs_label'] }}<span class="visually-hidden">{{ sprintf(__(' for %s (opens in a new window)', 'sage'), $item['title']) }}</span>
              <span class="shop-link__icon" aria-hidden="true">↗</span>
            </a>
          @endif
        </p>
      @endif
    </div>
  </div>
</article>
