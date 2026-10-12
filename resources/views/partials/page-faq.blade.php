{{--
  A short question-and-answer section (native details/summary, same list as the Projects FAQ).
  Pass: id, headingId, h2, items (list of ['title' => …, 'text' => …]).
  Optional: eyebrow, intro, alt (tinted band), cta (label) + ctaHref, foot (trusted HTML line under the list).
--}}
@php
  $items = $items ?? [];
  $alt = ! empty($alt);
@endphp
@if ($items !== [])
  <section class="pf-section{{ $alt ? ' pf-section--alt' : '' }} work-guide work-faq" id="{{ $id }}" aria-labelledby="{{ $headingId }}">
    <div class="container wide">
      <div class="work-faq__head">
        <div>
          @if (! empty($eyebrow))
            <p class="eyebrow">{{ $eyebrow }}</p>
          @endif
          <h2 id="{{ $headingId }}" class="display-title is-section">{{ $h2 }}</h2>
          @if (! empty($intro))
            <p class="work-faq__intro">{{ $intro }}</p>
          @endif
        </div>
        @if (! empty($cta))
          <a class="btn btn--sm" href="{{ esc_url($ctaHref ?? home_url('/contact/')) }}">
            {!! \App\mh_svg_icon('mail', 14) !!} {{ $cta }}
          </a>
        @endif
      </div>
      <div class="faq-list">
        @foreach ($items as $i => $faq)
          <details {{ $i === 0 ? 'open' : '' }}>
            <summary>{{ $faq['title'] }}</summary>
            <p>{{ $faq['text'] }}</p>
          </details>
        @endforeach
      </div>
      @if (! empty($foot))
        <p class="work-faq__foot">{!! wp_kses_post($foot) !!}</p>
      @endif
    </div>
  </section>
@endif
