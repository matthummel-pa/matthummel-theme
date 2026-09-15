{{-- Home work case-study card. Expects $p work card array; $featured optional. --}}
@php
  $featured = ! empty($featured);
  $shots = array_values(array_filter($p['screenshots'] ?? []));
  if ($shots === [] && ! empty($p['image'])) {
    $shots = [$p['image']];
  }
  $desktop = $shots[0] ?? '';
  $mobile = $shots[1] ?? ($shots[0] ?? '');
  $isConcept = ! empty($p['is_concept']) || empty($p['product_id']);
  $details = esc_url($p['url'] ?? \App\mh_concept_page_url((string) ($p['slug'] ?? '')));
  $challenge = trim((string) ($p['challenge'] ?? ''));
  $approach = trim((string) ($p['approach'] ?? ''));
  $blurb = trim((string) ($p['blurb'] ?? ''));
@endphp
<article class="h-case{{ $featured ? ' h-case--featured' : '' }}" aria-label="{{ esc_attr($p['title'] ?? '') }}">
  <div class="h-case__media" aria-hidden="{{ $desktop === '' ? 'true' : 'false' }}">
    @if ($desktop !== '')
      <figure class="h-case__desktop">
        <img
          src="{{ esc_url($desktop) }}"
          alt="{{ esc_attr(($p['title'] ?? '') . ' — desktop') }}"
          width="960"
          height="600"
          loading="{{ $featured ? 'eager' : 'lazy' }}"
          decoding="async"
        >
        <figcaption>{{ __('Desktop', 'sage') }}</figcaption>
      </figure>
    @endif
    @if ($mobile !== '' && $mobile !== $desktop)
      <figure class="h-case__mobile">
        <img
          src="{{ esc_url($mobile) }}"
          alt="{{ esc_attr(($p['title'] ?? '') . ' — secondary view') }}"
          width="360"
          height="640"
          loading="lazy"
          decoding="async"
        >
        <figcaption>{{ __('Detail', 'sage') }}</figcaption>
      </figure>
    @elseif ($desktop !== '')
      <figure class="h-case__mobile h-case__mobile--echo">
        <img
          src="{{ esc_url($desktop) }}"
          alt=""
          width="360"
          height="640"
          loading="lazy"
          decoding="async"
        >
        <figcaption>{{ __('Mobile-ready', 'sage') }}</figcaption>
      </figure>
    @endif
  </div>

  <div class="h-case__body">
    <div class="h-case__meta">
      @if ($isConcept)
        <span class="h-case__concept">{{ __('Concept', 'sage') }}</span>
      @else
        @include('partials.spec-badge', ['p' => $p])
      @endif
      @if (! empty($p['cat']))
        <span class="h-work-cat-badge h-work-cat-badge--sm">{{ $p['cat'] }}</span>
      @endif
      @if (! empty($p['place']))
        <span class="h-work-place h-work-place--sm">{!! \App\mh_svg_icon('map', 12) !!} {{ $p['place'] }}</span>
      @endif
    </div>

    <h3 class="h-case__title">
      <a href="{{ $details }}">{{ $p['title'] ?? '' }}</a>
    </h3>

    @if ($challenge !== '' || $approach !== '')
      <dl class="h-case__story">
        @if ($challenge !== '')
          <div>
            <dt>{{ __('Problem', 'sage') }}</dt>
            <dd>{{ wp_trim_words($challenge, 36, '…') }}</dd>
          </div>
        @endif
        @if ($approach !== '')
          <div>
            <dt>{{ __('Solution', 'sage') }}</dt>
            <dd>{{ wp_trim_words($approach, 40, '…') }}</dd>
          </div>
        @endif
      </dl>
    @elseif ($blurb !== '')
      <p class="h-case__blurb">{{ $blurb }}</p>
    @endif
  </div>
</article>
