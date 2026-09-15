{{-- Hire / process sections moved from Home (3.5.31). Used on About. --}}
@php
  $processSteps = [
    [
      'num' => '01',
      'title' => __('Write.', 'sage'),
      'body' => __('Tell me who the site is for, what is broken, and what a win looks like. A short note is enough.', 'sage'),
      'timing' => __('1–2 days', 'sage'),
      'gets' => [
        __('A reply with questions', 'sage'),
        __('An honest no if I am the wrong fit', 'sage'),
        __('No sales pitch', 'sage'),
      ],
    ],
    [
      'num' => '02',
      'title' => __('Scope.', 'sage'),
      'body' => __('I send a plain list of work, a rough timeline, and what is out of scope. You approve or push back.', 'sage'),
      'timing' => __('2–4 days', 'sage'),
      'gets' => [
        __('Written scope', 'sage'),
        __('Clear out-of-scope list', 'sage'),
        __('No lock-in contract', 'sage'),
      ],
    ],
    [
      'num' => '03',
      'title' => __('Build.', 'sage'),
      'body' => __('I build on staged pages you can click. Every line ships after I have read and tested it.', 'sage'),
      'timing' => __('1–2 weeks', 'sage'),
      'gets' => [
        __('Staged previews', 'sage'),
        __('Reviewed code before it ships', 'sage'),
        __('Room for feedback mid-build', 'sage'),
      ],
    ],
    [
      'num' => '04',
      'title' => __('Yours.', 'sage'),
      'body' => __('You own the domain, hosting, database, and code. I leave a short admin guide and stay reachable for questions.', 'sage'),
      'timing' => __('Handoff', 'sage'),
      'gets' => [
        __('Full ownership', 'sage'),
        __('Plain-language admin notes', 'sage'),
        __('No monthly lock-in', 'sage'),
      ],
    ],
  ];

  $goodFit = [
    'yes' => [
      __('You need a WordPress site or web app you can own', 'sage'),
      __('You want clean code another developer can read', 'sage'),
      __('You have a clear goal — or want help naming one', 'sage'),
      __('You want written scope before work starts', 'sage'),
      __('You are a shop, agency, or hiring manager', 'sage'),
    ],
    'no' => [
      __('You need a designer — I am a developer (I can refer one)', 'sage'),
      __('You need a site in under a week', 'sage'),
      __('You want ongoing social or ads management', 'sage'),
      __('You need a large ecommerce platform from scratch', 'sage'),
    ],
  ];

  $values = [
    [
      'num' => '01',
      'icon' => 'briefcase',
      'headline' => __('You own it at handoff.', 'sage'),
      'body' => __('Hosting, domain, files, and login are yours before we close. No reseller seat.', 'sage'),
    ],
    [
      'num' => '02',
      'icon' => 'users',
      'headline' => __('Your team can edit it.', 'sage'),
      'body' => __('Pages use WordPress fields in wp-admin — not a page builder you have to rent forever.', 'sage'),
    ],
    [
      'num' => '03',
      'icon' => 'code',
      'headline' => __('Readable for the next developer.', 'sage'),
      'body' => __('Short files, clear names, and notes that explain why. Stack detail lives on the Code page.', 'sage'),
    ],
  ];

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

@include('partials.recruiter-glance')

<section class="h-process h-band" id="process" aria-labelledby="h-process-heading">
  <div class="container wide">
    <div class="h-section-shell">
      <div class="h-process__head">
        <div>
          <p class="h-section-label">{{ __('Process', 'sage') }}</p>
          <h2 id="h-process-heading" class="h-section__title">
            {{ \App\field('home_process_h2', __('How a project goes.', 'sage')) }}
          </h2>
          <p class="h-process__subhead">{{ __('Four steps. Written scope. You own everything at the end.', 'sage') }}</p>
        </div>
      </div>
      <div class="h-process__grid">
        @foreach ($processSteps as $step)
          <div class="h-process__step">
            <div class="h-process__step-head">
              <span class="h-process__num" aria-hidden="true">{{ $step['num'] }}</span>
              <span class="h-process__timing">
                {!! \App\mh_svg_icon('calendar', 13) !!}
                {{ $step['timing'] }}
              </span>
            </div>
            <h3 class="h-process__title">{{ $step['title'] }}</h3>
            <p class="h-process__body">{{ $step['body'] }}</p>
            <ul class="h-process__gets">
              @foreach ($step['gets'] as $item)
                <li>{{ $item }}</li>
              @endforeach
            </ul>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

<section class="h-fit h-band h-band--tint" id="fit" aria-labelledby="h-fit-heading">
  <div class="container wide">
    <div class="h-section-shell">
      <div class="h-fit__head">
        <p class="h-section-label">{{ __('Fit', 'sage') }}</p>
        <h2 id="h-fit-heading" class="h-section__title">{{ __('Is this a good fit?', 'sage') }}</h2>
        <p class="h-fit__intro">{{ __('A few honest checks before we spend time on a call.', 'sage') }}</p>
      </div>
      <div class="h-fit__grid">
        <div class="h-fit__col h-fit__col--yes">
          <p class="h-fit__col-label">
            <span class="h-fit__icon h-fit__icon--yes" aria-hidden="true">✓</span>
            {{ __('Good fit', 'sage') }}
          </p>
          <ul class="h-fit__list">
            @foreach ($goodFit['yes'] as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        </div>
        <div class="h-fit__col h-fit__col--no">
          <p class="h-fit__col-label">
            <span class="h-fit__icon h-fit__icon--no" aria-hidden="true">✕</span>
            {{ __('Not a fit', 'sage') }}
          </p>
          <ul class="h-fit__list">
            @foreach ($goodFit['no'] as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="h-principles h-band" id="principles" aria-labelledby="h-principles-heading">
  <div class="container wide">
    <div class="h-section-shell h-principles__shell">
      <div class="h-principles__head">
        <div>
          <p class="h-section-label">{{ __('How I work', 'sage') }}</p>
          <h2 id="h-principles-heading" class="h-section__title">
            {{ __('Sites shops can keep.', 'sage') }}
          </h2>
          <p class="h-principles__intro">
            {{ __('Written scope, editable pages, and a clean handoff. Stack detail is on Code.', 'sage') }}
          </p>
        </div>
      </div>
      <div class="h-principles__grid h-principles__grid--compact">
        @foreach ($values as $v)
          <article class="h-principle">
            <div class="h-principle__top">
              <span class="h-principle__num" aria-hidden="true">{{ $v['num'] }}</span>
              <span class="h-principle__icon" aria-hidden="true">
                {!! \App\mh_svg_icon($v['icon'], 22) !!}
              </span>
            </div>
            <h3 class="h-principle__headline">{{ $v['headline'] }}</h3>
            <p class="h-principle__body">{{ $v['body'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </div>
</section>

<section class="h-faq h-band h-band--tint" id="faq" aria-labelledby="h-faq-heading">
  <div class="container wide h-faq__inner">
    <div class="h-faq__sidebar">
      <p class="h-section-label">{{ __('Questions', 'sage') }}</p>
      <h2 id="h-faq-heading" class="h-section__title">{{ __('Common questions.', 'sage') }}</h2>
      <p class="h-faq__blurb">{!! sprintf(
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
