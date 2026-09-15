@php
  $posts   = \App\mh_home_journal_posts(5);
  $work    = array_slice(\App\mh_work_page_items(), 0, 4);
  $gh      = \App\Github::fetchUser(\App\mh_github_login());
  $ossData = \App\mh_home_oss_live_data(6);
  $ghUrl   = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');

  $marqueeItems = [
    ['WordPress sites', 'wordpress'],
    ['Themes & plugins', 'plugins'],
    ['Shops & agencies', 'briefcase'],
    ['Clean handoff', 'check'],
    ['You own the code', 'code'],
    ['Editable in wp-admin', 'layout-text'],
    ['Live demos', 'globe'],
    ['GPL licensed', 'book-open'],
  ];

  $values = [
    [
      'num'      => '01',
      'icon'     => 'briefcase',
      'headline' => 'You own it at handoff.',
      'body'     => 'Hosting, the domain, the files, and the login are yours before we close. No reseller seat. No lock-in.',
      'practice' => 'I leave deploy notes and a short admin guide so the next person is not guessing.',
    ],
    [
      'num'      => '02',
      'icon'     => 'users',
      'headline' => 'Your team can edit it.',
      'body'     => 'Pages use WordPress fields you change in wp-admin — not a page builder. If an owner cannot update hours or a product in a couple of minutes, the theme is not done.',
      'practice' => 'Before launch I walk every editable field and note anything unusual in plain English.',
    ],
    [
      'num'      => '03',
      'icon'     => 'code',
      'headline' => 'Readable for the next developer.',
      'body'     => 'Modern WordPress themes and focused plugins. Source on GitHub when it ships as a product. Stack details live on About for peers who want them.',
      'practice' => 'Short files, clear names, and commits that explain why — not clever abbreviations.',
    ],
  ];

  $processSteps = [
    [
      'num'    => '01',
      'title'  => 'Write.',
      'body'   => 'Tell me who the site is for, what\'s broken, and what a win looks like. A paragraph is plenty — no spec doc required.',
      'timing' => '1–2 days',
      'gets'   => ['Quick reply with questions', 'Honest answer if I\'m the wrong fit', 'No sales pitch'],
    ],
    [
      'num'    => '02',
      'title'  => 'Scope.',
      'body'   => 'I send a plain list of work, a rough timeline, and an explicit list of what\'s out of scope. You approve or push back.',
      'timing' => '2–4 days',
      'gets'   => ['Written scope document', 'Clear out-of-scope list', 'No lock-in or ongoing contracts'],
    ],
    [
      'num'    => '03',
      'title'  => 'Build.',
      'body'   => 'I use modern tools — including AI — on the repeatable parts. Every line ships after I have read and tested it. You get staged previews on real pages, not mockups.',
      'timing' => '1–2 weeks',
      'gets'   => ['Faster turnaround than traditional builds', 'Every line reviewed by me before it ships', 'Staged previews you can click through and give feedback on'],
    ],
    [
      'num'    => '04',
      'title'  => 'Yours.',
      'body'   => 'You own everything: the domain, the hosting, the database, the code. I write you a plain-language handoff guide and stay reachable for questions.',
      'timing' => 'Forever',
      'gets'   => ['Full ownership transfer', 'Plain-language admin guide', 'No lock-in, no monthly fee'],
    ],
  ];

  $goodFit = [
    'yes' => [
      'You need a WordPress platform or full-stack web application you can own',
      'You want a quick turnaround without cutting corners on quality',
      'You want clean code a future developer can read',
      'You have a clear idea of what you need — or want help figuring it out',
      'You want a written scope agreed before anything starts',
      'You\'re a shop, agency, or developer who needs a reliable sub',
    ],
    'no'  => [
      'You need a designer — I\'m a developer (I can refer you to one)',
      'You need a site in under a week',
      'You want ongoing social media or ad management',
      'You need an enterprise e-commerce platform from scratch',
      'You need someone to manage scope as it grows without any agreed boundaries',
    ],
  ];

  $faqItems = [
    [
      'q' => 'What does "you own it" actually mean?',
      'a' => 'The domain is in your name. The hosting account is yours. The database, the files, the code — all yours. I have no access after handoff unless you invite me. You can take everything to another developer tomorrow and they\'ll have what they need.',
    ],
    [
      'q' => 'Do you do design, or just development?',
      'a' => 'Development. I can work from your design, a reference site, or a well-described direction. For original design work, I\'ll refer you to someone who does it properly rather than half-guess at it.',
    ],
    [
      'q' => 'How long does a WordPress site usually take?',
      'a' => 'A simple site with a few pages and a contact form: two to three weeks. Something with custom fields, filtering, or a booking system: four to eight weeks. I\'ll give you a realistic estimate during scoping, not an optimistic one.',
    ],
    [
      'q' => 'Can I edit the site myself after you hand it off?',
      'a' => 'Yes — that\'s the whole point. Pages use standard WordPress fields so editing feels like filling in a form, not touching code. I\'ll document anything unusual in plain English before I hand off.',
    ],
    [
      'q' => 'Do you work with agencies on client projects?',
      'a' => 'Yes. I have worked as a silent sub on a handful of agency jobs. You keep the client relationship, I stay in the background. Rate is project-based. Write and tell me what you’re working on.',
    ],
    [
      'q' => 'Is GitHub the start of your career?',
      'a' => 'No. I have about 17 years of in-house employer web work. Most of that cannot be shown. Public GitHub is the trail I started in 2025.',
    ],
    [
      'q' => 'Do you have a Power Platform demo?',
      'a' => 'PowerApps, Power Automate, and InfoPath for federal agencies are on the hire page. There is no public demo.',
    ],
    [
      'q' => 'Do you only do WordPress?',
      'a' => \App\mh_adjacent_range_copy(),
    ],
    [
      'q' => 'What do you charge?',
      'a' => 'Theme install and brand starts around $400. A small shop site is usually $3,000–$6,000. Agency overflow is half-day, day, or a project floor. Full packages are on Services. Custom quotes when the scope is different.',
    ],
  ];
@endphp

{{-- ═══════════════════════════════════════════════════
     01 — HERO
     ═══════════════════════════════════════════════════ --}}
<section class="h-hero" aria-labelledby="h-hero-name">
  <div class="h-hero__atmosphere" aria-hidden="true">
    <span class="h-hero__blob h-hero__blob--a" data-parallax="0.12"></span>
    <span class="h-hero__blob h-hero__blob--b" data-parallax="0.07"></span>
    <span class="h-hero__mesh"></span>
  </div>
  <div class="container wide h-hero__inner">

    <div class="h-hero__copy">
      <p class="h-hero__kicker">
        <span class="h-hero__kicker-dot" aria-hidden="true"></span>
        {{ \App\field('home_kicker', __('WordPress · plugins · web apps', 'sage')) }}
      </p>

      <h1 id="h-hero-name" class="h-hero__name">
        {{ \App\field('home_h1', \App\mh_home_hero_default('h1')) }}
      </h1>

      <p class="h-hero__role">
        {{ \App\field('home_role', \App\mh_home_hero_default('role')) }}
      </p>

      <p class="h-hero__lede">
        {{ \App\field('home_lede', \App\mh_home_hero_default('lede')) }}
      </p>

      <div class="h-hero__actions">
        <a class="btn h-hero__cta" href="{{ esc_url(\App\field_href('home_cta_primary_url', '/hire/')) }}">
          {!! \App\mh_svg_icon('mail', 17) !!}
          {{ \App\field('home_cta_primary', __('Hire me', 'sage')) }}
        </a>
        <a class="h-text-arrow" href="{{ esc_url(\App\field_href('home_cta_secondary_url', '/projects/')) }}">
          {{ \App\field('home_cta_secondary', __('Browse projects', 'sage')) }}
          <span aria-hidden="true">→</span>
        </a>
      </div>

      <ul class="h-hero__proof" aria-label="{{ __('Proof points', 'sage') }}">
        <li class="h-hero__proof-item">
          <span class="h-hero__proof-label">{{ __('Experience', 'sage') }}</span>
          <span class="h-hero__proof-value">{{ \App\field('home_proof_1', __('17 years in-house web work', 'sage')) }}</span>
        </li>
        <li class="h-hero__proof-item">
          <span class="h-hero__proof-label">{{ __('Ownership', 'sage') }}</span>
          <span class="h-hero__proof-value">{{ \App\field('home_proof_2', __('GPL — you own the code', 'sage')) }}</span>
        </li>
        <li class="h-hero__proof-item">
          <span class="h-hero__proof-label">{{ __('Availability', 'sage') }}</span>
          <span class="h-hero__proof-value">{{ \App\field('home_proof_3', __('Open for full-time & contract', 'sage')) }}</span>
        </li>
      </ul>
    </div>

    <aside class="h-hero__viz" aria-label="{{ __('Selected work preview', 'sage') }}">
      <div class="h-hero-work" aria-hidden="false">

        {{-- Browser-chrome frame containing the featured project screenshot --}}
        @php $fp = $work[0] ?? null; @endphp
        @if ($fp)
          <div class="h-hero-work__frame">
            <div class="h-hero-work__chrome" aria-hidden="true">
              <span class="h-hero-work__dot"></span>
              <span class="h-hero-work__dot"></span>
              <span class="h-hero-work__dot"></span>
              <span class="h-hero-work__addr">matthummel.com/projects</span>
            </div>
            <a class="h-hero-work__main-link"
               href="{{ esc_url($fp['url'] ?? \App\mh_work_listing_url()) }}"
               aria-label="{{ esc_attr(__('View ', 'sage') . ($fp['title'] ?? '') . __(' project', 'sage')) }}">
              @if (! empty($fp['image']))
                <img
                  class="h-hero-work__main-img"
                  src="{{ esc_url($fp['image']) }}"
                  alt="{{ esc_attr(($fp['title'] ?? '') . ' — ' . ($fp['cat'] ?? '') . ' website') }}"
                  width="640" height="400"
                  loading="eager"
                  decoding="async"
                >
              @else
                <div class="h-hero-work__main-img h-hero-work__main-img--text">
                  {{ $fp['title'] ?? '' }}
                </div>
              @endif
              <span class="h-hero-work__main-overlay" aria-hidden="true">
                <span class="h-hero-work__main-name">{{ $fp['title'] ?? '' }}</span>
                <span class="h-hero-work__main-cat">{{ $fp['cat'] ?? '' }}</span>
              </span>
            </a>
          </div>
        @endif

        {{-- Two smaller thumbnails --}}
        @php $miniWork = array_slice($work, 1, 2); @endphp
        @if (! empty($miniWork))
          <div class="h-hero-work__grid">
            @foreach ($miniWork as $pw)
              <a class="h-hero-work__mini"
                 href="{{ esc_url($pw['url'] ?? \App\mh_work_listing_url()) }}"
                 aria-label="{{ esc_attr($pw['title'] ?? '') }}">
                @if (! empty($pw['image']))
                  <img
                    src="{{ esc_url($pw['image']) }}"
                    alt="{{ esc_attr($pw['title'] ?? '') }}"
                    width="300" height="180"
                    loading="lazy"
                    decoding="async"
                  >
                @else
                  <span class="h-hero-work__mini-fallback" aria-hidden="true">
                    {{ $pw['title'] ?? '' }}
                  </span>
                @endif
                <span class="h-hero-work__mini-label">{{ $pw['title'] ?? '' }}</span>
              </a>
            @endforeach
          </div>
        @endif

        {{-- Footer strip — compact stats + view-all link --}}
        <div class="h-hero-work__foot">
          @php $totalW = count(\App\mh_work_page_items()); @endphp
          <span class="h-hero-work__foot-count">
            {!! \App\mh_svg_icon('briefcase', 13) !!}
            {{ $totalW }} projects
          </span>
          @if (\App\mh_is_hireable($gh))
            <span class="h-hero-work__foot-avail">
              @include('partials.avail-mark', ['gh' => $gh])
              {{ \App\mh_availability_label($gh, __('Open to work', 'sage')) }}
            </span>
          @endif
        </div>

      </div>
    </aside>

  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     TICKER — scrolling skill names
     ═══════════════════════════════════════════════════ --}}
<div class="h-ticker" aria-hidden="true">
  <div class="h-ticker__track">
    @foreach (array_merge($marqueeItems, $marqueeItems) as [$label, $icon])
      <span class="h-ticker__item">
        {!! \App\mh_svg_icon($icon, 16) !!}
        {{ $label }}
      </span>
      <span class="h-ticker__sep" aria-hidden="true">·</span>
    @endforeach
  </div>
</div>

@include('partials.recruiter-glance')

{{-- Sticky section pills — desktop pills + mobile dropdown --}}
<nav class="h-page-nav" data-section-nav aria-label="{{ __('On this page', 'sage') }}">
  <div class="container wide h-page-nav__inner">
    <p class="h-page-nav__label">{{ __('On this page', 'sage') }}</p>
    <details class="h-page-nav__mobile">
      <summary class="h-page-nav__mobile-summary">
        <span data-section-nav-current>{{ __('Glance', 'sage') }}</span>
      </summary>
      <div class="h-page-nav__mobile-list" role="list">
        <a class="h-page-nav__pill" role="listitem" href="#glance">{{ __('Glance', 'sage') }}</a>
        <a class="h-page-nav__pill" role="listitem" href="#about">{{ __('About', 'sage') }}</a>
        <a class="h-page-nav__pill" role="listitem" href="#help">{{ __('Help', 'sage') }}</a>
        <a class="h-page-nav__pill" role="listitem" href="#process">{{ __('Process', 'sage') }}</a>
        <a class="h-page-nav__pill" role="listitem" href="#receive">{{ __('Receive', 'sage') }}</a>
        <a class="h-page-nav__pill" role="listitem" href="#fit">{{ __('Fit', 'sage') }}</a>
        <a class="h-page-nav__pill" role="listitem" href="#work">{{ __('Projects', 'sage') }}</a>
        <a class="h-page-nav__pill" role="listitem" href="#journal">{{ __('Journal', 'sage') }}</a>
        <a class="h-page-nav__pill" role="listitem" href="#faq">{{ __('FAQ', 'sage') }}</a>
      </div>
    </details>
    <div class="h-page-nav__pills" role="list">
      <a class="h-page-nav__pill" role="listitem" href="#glance">{{ __('Glance', 'sage') }}</a>
      <a class="h-page-nav__pill" role="listitem" href="#about">{{ __('About', 'sage') }}</a>
      <a class="h-page-nav__pill" role="listitem" href="#help">{{ __('Help', 'sage') }}</a>
      <a class="h-page-nav__pill" role="listitem" href="#process">{{ __('Process', 'sage') }}</a>
      <a class="h-page-nav__pill" role="listitem" href="#receive">{{ __('Receive', 'sage') }}</a>
      <a class="h-page-nav__pill" role="listitem" href="#fit">{{ __('Fit', 'sage') }}</a>
      <a class="h-page-nav__pill" role="listitem" href="#work">{{ __('Projects', 'sage') }}</a>
      <a class="h-page-nav__pill" role="listitem" href="#journal">{{ __('Journal', 'sage') }}</a>
      <a class="h-page-nav__pill" role="listitem" href="#faq">{{ __('FAQ', 'sage') }}</a>
    </div>
  </div>
</nav>

<section class="h-about h-band" id="about" aria-labelledby="h-about-heading" itemscope itemtype="https://schema.org/Person">
  <meta itemprop="name" content="Matt Hummel">
  <meta itemprop="jobTitle" content="Full-Stack Developer and WordPress Specialist">
  <!-- address omitted: marketing SEO is skill-first, not local -->
  <div class="container wide">

    {{-- Two-column: bio left, sidebar right --}}
    <div class="h-about-v2">

      {{-- Left: photo + bio --}}
      <div class="h-about-v2__main">
        <div class="h-about-v2__photo-row">
          @include('partials.profile-photo', [
            'size'       => 220,
            'class'      => 'profile-photo h-about__img',
            'eager'      => false,
            'decorative' => false,
          ])
          <div class="h-about__meta">
            <span class="h-meta-item">{!! \App\mh_svg_icon('wordpress', 14) !!} {{ __('WordPress · themes · plugins', 'sage') }}</span>
            <span class="h-meta-item">{!! \App\mh_svg_icon('briefcase', 14) !!} {{ __('Full-stack developer', 'sage') }}</span>
            <span class="h-meta-item" itemprop="url">
              {!! \App\mh_svg_icon('github', 14) !!}
              <a href="https://github.com/matthummel-pa" rel="me noopener" target="_blank">@matthummel-pa</a>
            </span>
          </div>
        </div>

        <div class="h-about-v2__copy">
          <p class="h-section-label">About me</p>
          <h2 id="h-about-heading" class="h-about__heading">
            {{ \App\field('home_about_h2', __('The work I can share.', 'sage')) }}
          </h2>
          <p class="h-about__text" itemprop="description">
            {{ \App\field('home_about_text', __('I started in higher-ed marketing. The public trail is WordPress themes, plugins, and builds on GitHub.', 'sage')) }}
          </p>
          <p class="h-about__text">
            {{ \App\field('home_about_p2', __('The gallery is themes and plugins I ship — not a client grid. Agency-sub work stays in the background. Stack notes are on About.', 'sage')) }}
          </p>
          <div class="h-about__links">
            <a class="h-text-arrow" href="{{ home_url('/about/') }}">{{ __('Full background & stack', 'sage') }} →</a>
          </div>
        </div>
      </div>

      {{-- Audience doors — availability lives in the glance and header --}}
      <div class="h-about-v2__sidebar">

        {{-- Who's welcome --}}
        <div class="h-about-who">
          <p class="h-about-who__label">You're welcome here if you're a…</p>
          <ul class="h-about-who__list">
            <li>
              <span class="h-about-who__icon">{!! \App\mh_svg_icon('briefcase', 16) !!}</span>
              <span>
                <strong>Recruiter or hiring manager</strong> — role, stack, and employers are in the glance. Resume is on <a href="{{ home_url('/hire/') }}">Hire</a>.
              </span>
            </li>
            <li>
              <span class="h-about-who__icon">{!! \App\mh_svg_icon('users', 16) !!}</span>
              <span>
                <strong>Agency or studio</strong> — I have worked as a silent sub on a handful of agency jobs. You keep the relationship; I stay in the background.
              </span>
            </li>
            <li>
              <span class="h-about-who__icon">{!! \App\mh_svg_icon('code', 16) !!}</span>
              <span>
                <strong>Fellow developer</strong> — browse the code, ask questions, copy whatever helps. No credit required, though it's always appreciated.
              </span>
            </li>
            <li>
              <span class="h-about-who__icon">{!! \App\mh_svg_icon('globe', 16) !!}</span>
              <span>
                <strong>Shop or small business</strong> — I build WordPress sites you can actually run yourself.
              </span>
            </li>
          </ul>
        </div>

      </div>
    </div>
  </div>
</section>

{{-- What I help with — outcomes, not a tool parade --}}
@php
  $helpCards = [
    [
      'icon' => 'wordpress',
      'title' => \App\field('home_build_1_title', __('WordPress sites', 'sage')),
      'text' => \App\field('home_build_1_text', __('Clean, fast, and editable. Shops get something they own — not a subscription they rent.', 'sage')),
    ],
    [
      'icon' => 'plugins',
      'title' => \App\field('home_build_2_title', __('Plugins & tools', 'sage')),
      'text' => \App\field('home_build_2_text', __('Custom PHP when WordPress needs a new part. Small, focused, and readable.', 'sage')),
    ],
    [
      'icon' => 'code',
      'title' => \App\field('home_build_3_title', __('Full-stack web apps', 'sage')),
      'text' => \App\field('home_build_3_text', __('Interfaces, services, and APIs built as one maintainable system when a theme is not enough.', 'sage')),
    ],
  ];
@endphp
<section class="h-skills h-band h-band--tint" id="help" aria-labelledby="h-help-heading">
  <div class="container wide">
    <div class="h-skills__head">
      <div>
        <p class="h-section-label">{{ __('Services', 'sage') }}</p>
        <h2 id="h-help-heading" class="h-section__title">{{ \App\field('home_build_h2', __('What I help with', 'sage')) }}</h2>
      </div>
      <p class="h-skills__note">
        {{ __('Plain outcomes for shops and agencies. Developers who want the stack can read About.', 'sage') }}
      </p>
    </div>

    <div class="h-help-grid">
      @foreach ($helpCards as $card)
        <article class="h-help-card">
          <span class="h-help-card__icon" aria-hidden="true">{!! \App\mh_svg_icon($card['icon'], 22) !!}</span>
          <h3 class="h-help-card__title">{{ $card['title'] }}</h3>
          <p class="h-help-card__text">{{ $card['text'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     04 — HOW A PROJECT GOES
     ═══════════════════════════════════════════════════ --}}
<section class="h-process h-band" id="process" aria-labelledby="h-process-heading">
  <div class="container wide">
    <div class="h-section-shell">
      <div class="h-process__head">
        <div>
          <p class="h-section-label">Process</p>
          <h2 id="h-process-heading" class="h-section__title">{{ \App\field('home_process_h2', __('How a project goes.', 'sage')) }}</h2>
          <p class="h-process__subhead">Four steps. Written scope. You own everything at the end.</p>
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

{{-- What you receive — interactive deliverables walkthrough --}}
@php
  $receiveVideo = trim((string) \App\field('home_receive_video', ''));
  $receiveSteps = [
    [
      'id' => 'fields',
      'icon' => 'layout-text',
      'title' => __('Editable WordPress fields', 'sage'),
      'body' => __('Page content lives in wp-admin fields you can change — headlines, blurbs, CTAs — without touching theme files.', 'sage'),
      'preview' => __('Page content (theme)', 'sage'),
    ],
    [
      'id' => 'repo',
      'icon' => 'github',
      'title' => __('Repository access', 'sage'),
      'body' => __('GitHub-first delivery. You get the repo (or a clean handoff zip) so another developer can keep shipping.', 'sage'),
      'preview' => __('matthummel-pa / your-theme', 'sage'),
    ],
    [
      'id' => 'deploy',
      'icon' => 'server',
      'title' => __('Deployment notes', 'sage'),
      'body' => __('How to install or update the theme on your host, written for the next person — not only for me.', 'sage'),
      'preview' => __('Install → activate → edit', 'sage'),
    ],
    [
      'id' => 'guide',
      'icon' => 'book-open',
      'title' => __('Admin guide', 'sage'),
      'body' => __('HTML docs in the pack: install, Customizer, FAQ, support. Same files buyers get under Documentation/.', 'sage'),
      'preview' => __('Documentation / buyer-guide', 'sage'),
    ],
  ];
@endphp
<section class="h-receive h-band h-band--tint" id="receive" aria-labelledby="h-receive-heading" data-receive-walkthrough>
  <div class="container wide">
    <div class="h-receive__head">
      <p class="h-section-label">{{ __('Handoff', 'sage') }}</p>
      <h2 id="h-receive-heading" class="h-section__title">
        {{ \App\field('home_receive_h2', __('What you receive', 'sage')) }}
      </h2>
      <p class="h-receive__lede">
        {{ \App\field('home_receive_lede', __('Editable fields, the repo, deploy notes, and an admin guide — so the site stays yours after handoff.', 'sage')) }}
      </p>
    </div>

    <div class="h-receive__layout">
      <div class="h-receive__steps" role="tablist" aria-label="{{ __('Deliverables', 'sage') }}">
        @foreach ($receiveSteps as $i => $step)
          <button
            type="button"
            class="h-receive__step{{ $i === 0 ? ' is-active' : '' }}"
            role="tab"
            id="receive-tab-{{ $step['id'] }}"
            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
            aria-controls="receive-panel-{{ $step['id'] }}"
            data-receive-step="{{ $step['id'] }}"
          >
            <span class="h-receive__step-icon" aria-hidden="true">{!! \App\mh_svg_icon($step['icon'], 18) !!}</span>
            <span class="h-receive__step-copy">
              <strong>{{ $step['title'] }}</strong>
              <span>{{ $step['body'] }}</span>
            </span>
          </button>
        @endforeach
      </div>

      <div class="h-receive__stage">
        @if ($receiveVideo !== '')
          <figure class="h-receive__video">
            <video controls playsinline preload="metadata" poster="">
              <source src="{{ esc_url($receiveVideo) }}">
            </video>
            <figcaption>{{ \App\field('home_receive_caption', __('Owner updating a page in wp-admin — fields you can edit without a developer.', 'sage')) }}</figcaption>
          </figure>
        @else
          @foreach ($receiveSteps as $i => $step)
            <div
              class="h-receive__panel{{ $i === 0 ? ' is-active' : '' }}"
              role="tabpanel"
              id="receive-panel-{{ $step['id'] }}"
              aria-labelledby="receive-tab-{{ $step['id'] }}"
              @if ($i !== 0) hidden @endif
              data-receive-panel="{{ $step['id'] }}"
            >
              <div class="h-receive__mock" aria-hidden="true">
                <div class="h-receive__mock-chrome">
                  <span></span><span></span><span></span>
                  <em>{{ $step['preview'] }}</em>
                </div>
                <div class="h-receive__mock-body h-receive__mock-body--{{ $step['id'] }}">
                  @if ($step['id'] === 'fields')
                    <div class="h-receive__field"><label></label><i></i></div>
                    <div class="h-receive__field"><label></label><i class="is-wide"></i></div>
                    <div class="h-receive__field"><label></label><i class="is-tall"></i></div>
                    <span class="h-receive__cursor"></span>
                  @elseif ($step['id'] === 'repo')
                    <ul>
                      <li>app/</li>
                      <li>resources/</li>
                      <li>style.css</li>
                      <li>README.md</li>
                    </ul>
                  @elseif ($step['id'] === 'deploy')
                    <ol>
                      <li>{{ __('Upload or update the theme', 'sage') }}</li>
                      <li>{{ __('Activate in Appearance', 'sage') }}</li>
                      <li>{{ __('Edit page content in wp-admin', 'sage') }}</li>
                      <li>{{ __('Keep the handoff notes', 'sage') }}</li>
                    </ol>
                  @else
                    <ol>
                      <li>Install zip</li>
                      <li>Customizer identity</li>
                      <li>Edit page fields</li>
                      <li>Support FAQ</li>
                    </ol>
                  @endif
                </div>
              </div>
              <p class="h-receive__caption">
                {{ \App\field('home_receive_caption', __('Owner updating a page in wp-admin — fields you can edit without a developer.', 'sage')) }}
              </p>
            </div>
          @endforeach
        @endif
      </div>
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     04b — GOOD FIT / NOT A FIT
     ═══════════════════════════════════════════════════ --}}
<section class="h-fit h-band" id="fit" aria-labelledby="h-fit-heading">
  <div class="container wide">
    <div class="h-section-shell">
      <div class="h-fit__head">
        <p class="h-section-label">Honest expectations</p>
        <h2 id="h-fit-heading" class="h-section__title">Is this a good fit?</h2>
        <p class="h-fit__intro">Most conversations start with “how much does a website cost?” These answers might save us both time.</p>
      </div>
      <div class="h-fit__grid">
        <div class="h-fit__col h-fit__col--yes">
          <p class="h-fit__col-label">
            <span class="h-fit__icon h-fit__icon--yes" aria-hidden="true">✓</span>
            Good fit
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
            Not a fit
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

{{-- ═══════════════════════════════════════════════════
     05 — VALUES / PRINCIPLES
     ═══════════════════════════════════════════════════ --}}
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
            {{ __('Written scope, editable pages, and a clean handoff. Deep stack notes stay on About for developers and hiring managers.', 'sage') }}
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
            @if (! empty($v['practice']))
              <div class="h-principle__practice">
                <span class="h-principle__practice-label">{{ __('In practice', 'sage') }}</span>
                <p class="h-principle__practice-text">{{ $v['practice'] }}</p>
              </div>
            @endif
          </article>
        @endforeach
      </div>
    </div>

  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     06 — SELECTED WORK
     ═══════════════════════════════════════════════════ --}}
@if (! empty($work))
@php
  $allWork = \App\mh_work_page_items();
  $totalProjects = count($allWork);
  $caseWork = \App\mh_home_case_study_cards($allWork, 3);
@endphp
<section class="h-section h-section--tinted h-band h-band--tint" id="work" aria-labelledby="h-work-heading">
  <div class="container wide">

    <div class="h-work-header">
      <div>
        <p class="h-section-label">Projects</p>
        <h2 id="h-work-heading" class="h-section__title">
          {{ \App\field('home_work_h2', __('WordPress concepts.', 'sage')) }}
        </h2>
        <p class="h-work-intro">
          {{ \App\field('home_work_intro', __('Sample WordPress themes and plugins. Each one has a short story and a live demo when I have one. Employer work stays private unless a shop asks to show it.', 'sage')) }}
        </p>
      </div>
      <div class="h-work-header__meta">
        <span class="h-work-count">{{ $totalProjects }} projects</span>
        <a class="h-text-arrow" href="{{ esc_url(\App\mh_work_listing_url()) }}">Browse all →</a>
      </div>
    </div>

    <div class="h-case-list">
      @foreach ($caseWork as $i => $p)
        @include('partials.home-case-card', ['p' => $p, 'featured' => $i === 0])
      @endforeach
    </div>

  </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════
     07 — OPEN SOURCE  (live GitHub API data)
     ═══════════════════════════════════════════════════ --}}
<section class="h-section" id="code" aria-labelledby="h-oss-heading">
  <div class="container wide">
    <div class="h-section__head">
      <div>
        <p class="h-section-label">{{ __('Open source', 'sage') }}</p>
        <h2 id="h-oss-heading" class="h-section__title">{{ __('Code you can browse.', 'sage') }}</h2>
      </div>
      <a class="h-text-arrow" href="{{ home_url('/code/') }}">All repos →</a>
    </div>

    {{-- GitHub live panel --}}
    <div class="h-gh-panel">

      {{-- Left: profile + stats --}}
      <div class="h-gh-panel__profile">
        @if (! empty($ossData['profile']['avatar']))
          <img class="h-gh-panel__avatar" src="{{ esc_url($ossData['profile']['avatar']) }}" alt="{{ esc_attr($ossData['profile']['name'] ?: 'GitHub') }}" width="56" height="56" loading="lazy" decoding="async">
        @endif
        <div class="h-gh-panel__info">
          <p class="h-gh-panel__name">
            {!! \App\mh_svg_icon('github', 16) !!}
            <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">@matthummel-pa</a>
          </p>
          <div class="h-gh-panel__stats">
            @if (! empty($ossData['profile']['public_repos']))
              <span><strong>{{ number_format_i18n($ossData['profile']['public_repos']) }}</strong> repos</span>
            @endif
            @if (! empty($ossData['profile']['followers']))
              <span><strong>{{ number_format_i18n($ossData['profile']['followers']) }}</strong> followers</span>
            @endif
            @if (! empty($ossData['profile']['created']))
              <span>On GitHub since <strong>{{ $ossData['profile']['created'] }}</strong></span>
            @endif
          </div>
        </div>
        <span class="h-gh-panel__live-badge" aria-label="Live data from GitHub API">
          <span class="h-badge__dot" aria-hidden="true"></span>
          Live
        </span>
      </div>

      {{-- Right: activity feed --}}
      @if (! empty($ossData['events']))
        <div class="h-gh-panel__feed">
          <p class="h-gh-panel__feed-label">Recent activity</p>
          <ul class="h-gh-feed" role="list">
            @foreach (array_slice($ossData['events'], 0, 3) as $ev)
              @php
                $evIcon = match ($ev['type']) {
                  'PushEvent'          => 'code',
                  'ReleaseEvent'       => 'globe',
                  'PullRequestEvent'   => 'code',
                  'CreateEvent'        => 'code',
                  'IssuesEvent'        => 'search',
                  'IssueCommentEvent'  => 'pen',
                  default              => 'code',
                };
                $evWhen = $ev['when'] ? human_time_diff(strtotime($ev['when'])).' ago' : '';
              @endphp
              <li class="h-gh-feed__item">
                <span class="h-gh-feed__icon" aria-hidden="true">{!! \App\mh_svg_icon($evIcon, 14) !!}</span>
                <span class="h-gh-feed__text">
                  @if ($ev['url'])
                    <a href="{{ esc_url($ev['url']) }}" rel="noopener" target="_blank">{{ $ev['text'] }}</a>
                  @else
                    {{ $ev['text'] }}
                  @endif
                </span>
                @if ($evWhen)
                  <time class="h-gh-feed__when" datetime="{{ esc_attr($ev['when']) }}">{{ $evWhen }}</time>
                @endif
              </li>
            @endforeach
          </ul>
        </div>
      @endif

    </div>

    {{-- Featured repos for developers; full activity lives on /code/ --}}
    @if (! empty($ossData['repos']))
      <div class="code-repos-shell code-repos-shell--featured">
        <div class="code-repos-shell__mesh" aria-hidden="true"></div>
        <div class="code-repos-shell__inner">
          <header class="code-repos-shell__head">
            <p class="eyebrow">{{ __('For developers', 'sage') }}</p>
            <h3 class="code-repos-shell__title">{{ __('Open-source WordPress and app repos', 'sage') }}</h3>
            <p class="sec-intro">{{ __('Fork what helps. Activity, skills, and docs live on the Code page.', 'sage') }}</p>
            <p class="code-repos-shell__meta">
              {{ sprintf(_n('%s repo', '%s repos', count($ossData['repos']), 'sage'), number_format_i18n(count($ossData['repos']))) }}
              · <a href="{{ home_url('/code/') }}">{{ __('Full Code page', 'sage') }}</a>
            </p>
          </header>
          <ol class="code-repos-grid code-repos-grid--featured">
            @foreach (array_slice($ossData['repos'], 0, 4) as $i => $r)
              <li class="code-repos-grid__item">
                @include('partials.repo-card', ['r' => $r, 'index' => $i + 1, 'variant' => 'featured'])
              </li>
            @endforeach
          </ol>
        </div>
      </div>
    @endif

  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     08 — FROM THE JOURNAL
     ═══════════════════════════════════════════════════ --}}
@php
  $journalFeatured = $posts[0] ?? null;
  $journalStack    = array_slice($posts, 1, 4);
  $rssUrl = home_url('/feed/');
@endphp
<section class="h-journal h-band h-band--tint" id="journal" aria-labelledby="h-writing-heading">
  <div class="container wide">

    {{-- Header ─ SEO-rich heading + intro + links --}}
    <div class="h-journal__head">
      <div class="h-journal__head-copy">
        <p class="h-section-label">Journal</p>
        <h2 id="h-writing-heading" class="h-section__title">
          {{ \App\field('home_write_h2', __('Notes from real WordPress work.', 'sage')) }}
        </h2>
        <p class="h-journal__intro">
          {{ \App\field('home_write_intro', __('Practical posts for shops and developers — handoffs, themes, and lessons from builds. Most include something you can reuse.', 'sage')) }}
        </p>
      </div>
      <div class="h-journal__head-links">
        <a class="h-text-arrow" href="{{ $writing }}">All posts →</a>
        <a class="h-journal__rss" href="{{ esc_url($rssUrl) }}" rel="alternate" type="application/rss+xml">
          {!! \App\mh_svg_icon('rss', 14) !!} RSS feed
        </a>
      </div>
    </div>

    @if (! empty($posts))

      <div class="h-journal__grid">

        {{-- Featured post ─ left column ─ big card --}}
        @if ($journalFeatured)
        @php $fp = $journalFeatured; @endphp
        <article class="h-journal__featured" itemscope itemtype="https://schema.org/BlogPosting">
          <meta itemprop="author" content="Matt Hummel">

          {{-- Latest badge --}}
          <div class="h-journal__badge">
            {!! \App\mh_svg_icon('pen', 13) !!} {{ __('Featured note', 'sage') }}
          </div>

          {{-- Thumbnail --}}
          @if (! empty($fp['thumb']))
            <a class="h-journal__featured-img-link" href="{{ esc_url($fp['url']) }}" tabindex="-1" aria-hidden="true">
              <div class="h-journal__featured-img">
                <img
                  src="{{ esc_url($fp['thumb']) }}"
                  alt="{{ esc_attr($fp['title']) }}{{ $fp['cat'] ? ' — ' . esc_attr($fp['cat']) . ' post' : '' }}"
                  width="960" height="540"
                  loading="lazy"
                  decoding="async"
                  itemprop="image"
                >
              </div>
            </a>
          @else
            <a class="h-journal__featured-img-link" href="{{ esc_url($fp['url']) }}" tabindex="-1" aria-hidden="true">
              <div class="h-journal__featured-img h-journal__featured-img--text">
                <span>{{ wp_trim_words($fp['title'], 6, '') }}</span>
              </div>
            </a>
          @endif

          {{-- Content --}}
          <div class="h-journal__featured-body">
            <div class="h-journal__featured-meta">
              @if ($fp['cat'])
                <a class="h-journal__cat" href="{{ esc_url($fp['cat_url'] ?? $writing) }}" itemprop="articleSection">
                  {{ $fp['cat'] }}
                </a>
              @endif
              <time class="h-journal__date" datetime="{{ esc_attr($fp['date_iso'] ?? '') }}" itemprop="datePublished">
                {{ $fp['date'] }}
              </time>
              @if (! empty($fp['minutes']))
                <span class="h-journal__min">
                  {!! \App\mh_svg_icon('book-open', 13) !!}
                  {{ $fp['minutes'] }} min read
                </span>
              @endif
            </div>

            <h3 class="h-journal__featured-title" itemprop="headline">
              <a href="{{ esc_url($fp['url']) }}">{{ $fp['title'] }}</a>
            </h3>

            <p class="h-journal__featured-ex" itemprop="description">{{ $fp['ex'] }}</p>

            <a class="h-journal__read-link" href="{{ esc_url($fp['url']) }}">
              Read "{{ $fp['title'] }}" <span aria-hidden="true">→</span>
            </a>
          </div>
        </article>
        @endif

        {{-- Post stack ─ right column ─ digest list --}}
        @if (! empty($journalStack))
        <div class="h-journal__stack">
          <p class="h-journal__stack-label">More recent posts</p>

          @foreach ($journalStack as $post)
            <article class="h-journal__post{{ ! empty($post['deemphasize']) ? ' h-journal__post--quiet' : '' }}" itemscope itemtype="https://schema.org/BlogPosting">
              <meta itemprop="author" content="Matt Hummel">

              {{-- Small thumb --}}
              @if (! empty($post['thumb']))
                <a class="h-journal__post-thumb" href="{{ esc_url($post['url']) }}" tabindex="-1" aria-hidden="true">
                  <img
                    src="{{ esc_url($post['thumb']) }}"
                    alt="{{ esc_attr($post['title']) }}"
                    width="120" height="80"
                    loading="lazy"
                    decoding="async"
                  >
                </a>
              @else
                <div class="h-journal__post-thumb h-journal__post-thumb--text" aria-hidden="true">
                  {!! \App\mh_svg_icon('pen', 18) !!}
                </div>
              @endif

              {{-- Post info --}}
              <div class="h-journal__post-body">
                @if (! empty($post['deemphasize']))
                  <p class="h-journal__quiet-label">{{ __('Same-day notes', 'sage') }}</p>
                @endif
                @if ($post['cat'])
                  <a class="h-journal__cat h-journal__cat--sm" href="{{ esc_url($post['cat_url'] ?? $writing) }}" itemprop="articleSection">
                    {{ $post['cat'] }}
                  </a>
                @endif
                <h3 class="h-journal__post-title" itemprop="headline">
                  <a href="{{ esc_url($post['url']) }}">{{ $post['title'] }}</a>
                </h3>
                <div class="h-journal__post-meta">
                  <time datetime="{{ esc_attr($post['date_iso'] ?? '') }}" itemprop="datePublished">{{ $post['date'] }}</time>
                  @if (! empty($post['minutes']))
                    <span>· {{ $post['minutes'] }} min</span>
                  @endif
                </div>
              </div>

            </article>
          @endforeach

          <div class="h-journal__stack-footer">
            <a class="h-journal__rss h-journal__rss--sm" href="{{ esc_url($rssUrl) }}" rel="alternate" type="application/rss+xml">
              {!! \App\mh_svg_icon('rss', 13) !!} RSS
            </a>
          </div>
        </div>
        @endif

      </div>

    @else
      <p class="h-journal__empty">{{ \App\field('home_write_empty', __('New posts coming soon.', 'sage')) }}</p>
    @endif

  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     09 — FAQ
     ═══════════════════════════════════════════════════ --}}
<section class="h-faq h-band" id="faq" aria-labelledby="h-faq-heading">
  <div class="container wide h-faq__inner">

    <div class="h-faq__sidebar">
      <p class="h-section-label">Questions</p>
      <h2 id="h-faq-heading" class="h-section__title">Frequently asked.</h2>
      <p class="h-faq__blurb">Real questions from real conversations. If yours isn't here, <a href="{{ home_url('/contact/') }}">just ask</a>.</p>
      <p class="h-faq__hire-note">{{ __('Open for full-time, contract, freelance, and agency overflow. Write through the contact form.', 'sage') }}</p>
    </div>

    <div class="h-faq__list">
      @foreach ($faqItems as $i => $faq)
        <details class="h-faq__item" @if($i === 0) open @endif>
          <summary class="h-faq__q">{{ $faq['q'] }}</summary>
          <p class="h-faq__a">{{ $faq['a'] }}</p>
        </details>
      @endforeach
    </div>

  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     10 — CTA
     ═══════════════════════════════════════════════════ --}}
{{-- Back to top --}}
<div class="h-back-top-row">
  <a class="h-back-top" href="#h-hero-name">↑ Back to top</a>
</div>

<section class="cta-band h-cta" aria-labelledby="h-cta-heading" data-reveal>
  <div class="container wide cta-band-inner h-cta__inner">
    <div class="cta-band__copy">
      <p class="eyebrow eyebrow--on-dark">{{ __('Get in touch', 'sage') }}</p>
      <h2 id="h-cta-heading" class="display-title is-section h-cta__heading">{{ \App\field('home_help_h2', __('Hiring or building?', 'sage')) }}</h2>
      <p class="h-cta__body">{!! \App\field_html('home_help_p2', sprintf(
        __('Recruiters can <a href="/contact/">write through the contact form</a>. Shops can <a href="/projects/">browse projects</a>. I usually reply %s.', 'sage'),
        \App\mh_reply_sla('phrase')
      )) !!}</p>
    </div>
    <div class="cta-band__actions h-cta__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 17) !!}
        {{ \App\field('home_link_hello', __('Say hello', 'sage')) }}
      </a>
      <p class="cta-band__note">{{ \App\mh_reply_sla('note') }}</p>
    </div>
  </div>
</section>
