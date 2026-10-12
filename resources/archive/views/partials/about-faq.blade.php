{{-- Common questions on About (split out of about-hire-sections in 3.6.49 so FAQ can sit after Code). --}}
@php
  $faqItems = [
    [
      'q' => __('What does “you own it” mean?', 'sage'),
      'a' => __('The domain, hosting, database, and code are yours. After handoff I have no access unless you invite me. Another developer can pick up where I left off.', 'sage'),
    ],
    [
      'q' => __('Do you do design, or just development?', 'sage'),
      'a' => __('Development. I can work from your design or a clear reference. For original design work I will refer someone rather than half-guess it.', 'sage'),
    ],
    [
      'q' => __('How long does a WordPress site usually take?', 'sage'),
      'a' => __('A simple site is often two to three weeks. Custom fields or booking can take four to eight. I give a realistic estimate during scoping.', 'sage'),
    ],
    [
      'q' => __('Can I edit the site myself after handoff?', 'sage'),
      'a' => __('Yes. Pages use standard WordPress fields. I document anything unusual in plain English before handoff.', 'sage'),
    ],
    [
      'q' => __('Do you work with agencies?', 'sage'),
      'a' => __('Yes. I can stay in the background on agency work. Rate is project-based. Write and tell me what you need.', 'sage'),
    ],
    [
      'q' => __('Is GitHub the start of your career?', 'sage'),
      'a' => __('No. I have about 17 years of in-house employer web work. Most of that cannot be shown. Public GitHub is the trail I started in 2025.', 'sage'),
    ],
    [
      'q' => __('Do you only do WordPress?', 'sage'),
      'a' => \App\mh_adjacent_range_copy(),
    ],
  ];
@endphp

<section class="h-faq h-band h-band--tint" id="faq" aria-labelledby="h-faq-heading">
  <div class="container wide h-faq__inner">
    <div class="h-faq__sidebar">
      <p class="h-section-label">{{ __('Questions', 'sage') }}</p>
      <h2 id="h-faq-heading" class="h-section__title">{{ __('Common questions.', 'sage') }}</h2>
      <p class="h-faq__blurb">{!! sprintf(
        /* translators: %s: contact page URL */
        __('If yours is not here, <a href="%s">ask</a>.', 'sage'),
        esc_url(home_url('/contact/'))
      ) !!}</p>
    </div>
    <div class="h-faq__list">
      @foreach ($faqItems as $i => $faq)
        <details class="h-faq__item" @if ($i === 0) open @endif>
          <summary class="h-faq__q">{{ $faq['q'] }}</summary>
          <p class="h-faq__a">{{ $faq['a'] }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>
