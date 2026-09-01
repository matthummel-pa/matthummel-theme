@php
  $posts   = \App\mh_latest_posts(5);
  $work    = array_slice(\App\mh_work_page_items(), 0, 4);
  $gh      = \App\Github::fetchUser(\App\mh_github_login());
  $ossData = \App\mh_home_oss_live_data(3);
  $ghUrl   = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');

  $marqueeItems = [
    ['WordPress',     'wordpress'],
    ['PHP',           'php'],
    ['Sage / Blade',  'sage'],
    ['JavaScript',    'javascript'],
    ['TypeScript',    'typescript'],
    ['React',         'react'],
    ['Tailwind CSS',  'tailwind'],
    ['Vite',          'vite'],
    ['HTML & CSS',    'html'],
    ['Git',           'git'],
    ['GitHub',        'github'],
    ['Cursor AI',     'cursor-ai'],
    ['Claude',        'claude'],
    ['ChatGPT',       'chatgpt'],
    ['Gemini',        'gemini'],
    ['Notion',        'notion'],
    ['Google Drive',  'google-drive'],
    ['HubSpot',       'hubspot'],
    ['Rank Math SEO', 'rank-math'],
    ['MySQL',         'database'],
    ['VS Code',       'vscode'],
    ['Node.js',       'nodejs'],
    ['Netlify',       'netlify'],
    ['Supabase',      'supabase'],
  ];

  $skillGroups = [
    'WordPress'  => [
      ['WordPress',   'wordpress',  '#2271b1'],
      ['PHP',         'php',        '#7a86b8'],
      ['Sage / Blade','sage',       '#e3342f'],
      ['Plugins',     'plugins',    '#2271b1'],
    ],
    'JavaScript' => [
      ['JavaScript',  'javascript', '#f7df1e'],
      ['TypeScript',  'typescript', '#3178c6'],
      ['React',       'react',      '#61dafb'],
      ['Node.js',     'nodejs',     '#339933'],
    ],
    'Frontend'   => [
      ['Tailwind CSS','tailwind',   '#38bdf8'],
      ['Vite',        'vite',       '#646cff'],
      ['HTML & CSS',  'html',       '#e34c26'],
      ['Sass',        'sass',       '#cc6699'],
    ],
    'Dev Tools'  => [
      ['Git',         'git',        '#f05032'],
      ['GitHub',      'github',     '#111827'],
      ['VS Code',     'vscode',     '#007acc'],
      ['MySQL',       'database',   '#3ecf8e'],
      ['Netlify',     'netlify',    '#00c7b7'],
      ['Supabase',    'supabase',   '#3ecf8e'],
    ],
    'AI & Tooling' => [
      ['Cursor AI',   'cursor-ai',  '#111827'],
      ['Claude',      'claude',     '#d97706'],
      ['ChatGPT',     'chatgpt',    '#10a37f'],
      ['Gemini',      'gemini',     '#8E75B2'],
    ],
    'Workflow'   => [
      ['Notion',      'notion',     '#000000'],
      ['Google Drive','google-drive','#4285f4'],
      ['n8n',         'n8n',        '#EA4B71'],
    ],
    'Marketing'  => [
      ['HubSpot',     'hubspot',    '#ff7a59'],
      ['Rank Math SEO','rank-math', '#f50c24'],
    ],
  ];

  $values = [
    [
      'num'      => '01',
      'icon'     => 'briefcase',
      'headline' => 'You own the stack at handoff.',
      'body'     => 'Hosting, DNS, the database, and the Git repo sit in accounts under your name before we close. The Sage theme, plugins, env notes, and deploy path go with the site — so another developer can pick up without guessing.',
      'practice' => 'Separate host login, GitHub access, and wp-admin for the shop — never a seat under my reseller account.',
    ],
    [
      'num'      => '02',
      'icon'     => 'users',
      'headline' => 'wp-admin is part of the architecture.',
      'body'     => 'I build with Sage 11, Blade templates, Tailwind, and page fields shops edit in WordPress — not a page builder. If an owner cannot update hours or a product in a couple of minutes, the theme is not done.',
      'practice' => 'Before launch I walk every editable field, leave a short handoff note, and record a Loom when the edit path is non-obvious.',
    ],
    [
      'num'      => '03',
      'icon'     => 'code',
      'headline' => 'AI drafts. I ship the review.',
      'body'     => 'Cursor, Claude, and ChatGPT help with scaffolding, boilerplate, and first-pass PHP. I still read, test, and own every line that reaches production — Vite builds, PHP 8.3, and GitHub Actions included.',
      'practice' => 'A typical marketing site lands in about one to two weeks. Same review bar as a longer build — less time on work that does not need a human rewrite.',
    ],
    [
      'num'      => '04',
      'icon'     => 'plugins',
      'headline' => 'Small plugins. Clear hooks.',
      'body'     => 'Custom work lives in focused PHP plugins or theme modules with standard WordPress hooks, PHPDoc, and a clean uninstall path. If a feature fits in dozens of lines, it should not arrive as a kitchen-sink plugin.',
      'practice' => 'Most sites run a short plugin list. I audit and remove weight that does not earn its keep.',
    ],
    [
      'num'      => '05',
      'icon'     => 'book-open',
      'headline' => 'Readable PHP for the next developer.',
      'body'     => 'Blade stays thin; logic lives in App helpers with typed functions and explicit names. If another developer cannot follow a function in half a minute, I rewrite it before handoff.',
      'practice' => 'PHPDoc on public functions, short files, and Git commits that explain why — not clever abbreviations.',
    ],
    [
      'num'      => '06',
      'icon'     => 'pen',
      'headline' => 'Accessible markup and plain words.',
      'body'     => 'Semantic HTML, keyboard paths, and contrast that hold up. Labels, errors, and handoff notes read like they were written for a busy shop owner — welcoming to developers, clear for everyone else.',
      'practice' => 'Field labels and button text get the same care as the public page. If I have to explain a field, I rename it.',
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
      'body'   => 'I use modern tools — including AI assistants — to move faster on the parts that are repeatable. Every line ships only after I\'ve read and tested it myself. You get staged previews on real pages, not mockups.',
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
      'a' => 'Yes. I\'ve worked as a sub on agency projects. You keep the client relationship, I stay in the background. Rate is project-based. Write and tell me what you\'re working on.',
    ],
    [
      'q' => 'Do you build full-stack applications outside WordPress?',
      'a' => 'Yes. I build React and TypeScript interfaces, PHP or Node services, API integrations, authentication, and data-backed applications. WordPress is my specialty, not my only stack.',
    ],
  ];
@endphp

{{-- ═══════════════════════════════════════════════════
     01 — HERO
     ═══════════════════════════════════════════════════ --}}
@php
  $heroStats = [];
  if (! empty($gh['public_repos'])) {
    $heroStats[] = [
      'value' => number_format_i18n((int) $gh['public_repos']),
      'label' => __('Public repos', 'sage'),
      'href' => $ghUrl.'?tab=repositories',
    ];
  }
  if (! empty($gh['followers'])) {
    $heroStats[] = [
      'value' => number_format_i18n((int) $gh['followers']),
      'label' => __('Followers', 'sage'),
      'href' => $ghUrl.'?tab=followers',
    ];
  }
  $heroStats[] = [
    'value' => __('Remote', 'sage'),
    'label' => __('On-site welcome', 'sage'),
    'href' => null,
  ];
  $heroStats[] = [
    'value' => __('Full stack', 'sage'),
    'label' => __('WordPress focus', 'sage'),
    'href' => null,
  ];
  $ghLogin = \App\mh_github_login();
@endphp
<section class="h-hero" aria-labelledby="h-hero-name">
  <div class="container wide h-hero__inner">

    <div class="h-hero__copy">
      <p class="h-hero__kicker">
        {!! \App\mh_svg_icon('code', 14) !!}
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
          {{ \App\field('home_cta_secondary', __('Browse work', 'sage')) }}
          <span aria-hidden="true">→</span>
        </a>
      </div>
    </div>

    <aside class="h-hero__viz" aria-label="{{ __('Profile highlights', 'sage') }}">
      <div class="h-hero-illu">
        <span class="h-hero-illu__glow" aria-hidden="true"></span>
        <span class="h-hero-illu__orb h-hero-illu__orb--a" aria-hidden="true"></span>
        <span class="h-hero-illu__orb h-hero-illu__orb--b" aria-hidden="true"></span>

        <div class="h-hero-illu__card">
          <div class="h-hero-illu__chrome" aria-hidden="true">
            <span class="h-hero-illu__dot"></span>
            <span class="h-hero-illu__dot"></span>
            <span class="h-hero-illu__dot"></span>
            <span class="h-hero-illu__url">github.com/{{ $ghLogin }}</span>
          </div>

          <div class="h-hero-illu__head">
            <div class="h-hero-illu__identity">
              {!! \App\mh_svg_icon('github', 18) !!}
              <div>
                <p class="h-hero-illu__handle">{{ $ghLogin }}</p>
                <p class="h-hero-illu__meta">{{ __('Live profile signals', 'sage') }}</p>
              </div>
            </div>
            @if (\App\mh_is_hireable($gh))
              <span class="h-hero-illu__status">
                @include('partials.avail-mark', ['gh' => $gh])
                {{ \App\mh_availability_label($gh, __('Open', 'sage')) }}
              </span>
            @endif
          </div>

          <dl class="h-hero-illu__stats">
            @foreach ($heroStats as $stat)
              <div class="h-hero-illu__stat">
                <dt>
                  @if (! empty($stat['href']))
                    <a href="{{ esc_url($stat['href']) }}" rel="me noopener" target="_blank">{{ $stat['value'] }}</a>
                  @else
                    {{ $stat['value'] }}
                  @endif
                </dt>
                <dd>{{ $stat['label'] }}</dd>
              </div>
            @endforeach
          </dl>

          <a class="h-hero-illu__link" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
            {{ __('View GitHub', 'sage') }}
            <span aria-hidden="true">→</span>
          </a>
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

{{-- ═══════════════════════════════════════════════════
     02 — ABOUT
     ═══════════════════════════════════════════════════ --}}

{{-- Quiet jump links — kept below the fold so the hero stays one composition --}}
<nav class="h-page-nav container wide" aria-label="On this page">
  <span class="h-page-nav__label">On this page</span>
  <a href="#about">About</a>
  <a href="#skills">Skills</a>
  <a href="#process">Process</a>
  <a href="#work">Work</a>
  <a href="#journal">Journal</a>
  <a href="#faq">FAQ</a>
</nav>

<section class="h-about" id="about" aria-labelledby="h-about-heading" itemscope itemtype="https://schema.org/Person">
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
            <span class="h-meta-item">{!! \App\mh_svg_icon('code', 14) !!} Sage · Tailwind · Vite</span>
            <span class="h-meta-item">{!! \App\mh_svg_icon('code', 14) !!} Full-stack developer · WordPress specialist</span>
            <span class="h-meta-item" itemprop="url">
              {!! \App\mh_svg_icon('github', 14) !!}
              <a href="https://github.com/matthummel-pa" rel="me noopener" target="_blank">@matthummel-pa</a>
            </span>
          </div>
        </div>

        <div class="h-about-v2__copy">
          <p class="h-section-label">About me</p>
          <h2 id="h-about-heading" class="h-about__heading">
            {{ \App\field('home_about_h2', __('Full-stack developer. WordPress specialist. Open to collaboration.', 'sage')) }}
          </h2>
          <p class="h-about__text" itemprop="description">
            {{ \App\field('home_about_text', __('I\'ve spent more than 15 years building for the web, from accessible front ends to PHP applications, APIs, and deployment workflows. WordPress is my specialty because it combines a flexible development platform with an editor businesses can actually use.', 'sage')) }}
          </p>
          <p class="h-about__text">
            {{ \App\field('home_about_p2', __('I work with businesses that need dependable web software, agencies that need an experienced development partner, and developers who want to compare notes or reuse open-source code. Most of my public work is on GitHub, and you are welcome to fork it.', 'sage')) }}
          </p>
          <div class="h-about__links">
            <a class="h-text-arrow" href="{{ home_url('/about/') }}">Full background →</a>
            <a class="h-text-arrow" href="{{ home_url('/now/') }}">What I\'m doing now →</a>
            <a class="h-text-arrow" href="{{ home_url('/projects/') }}">{{ __('See example sites', 'sage') }} →</a>
          </div>
        </div>
      </div>

      {{-- Right: availability + audience cards --}}
      <div class="h-about-v2__sidebar">

        {{-- Availability card (GitHub hireable) --}}
        @if (\App\mh_is_hireable($gh))
          <div class="h-avail-card h-about-avail">
            <p class="h-avail-card__label">
              @include('partials.avail-mark', ['gh' => $gh])
              Current status
            </p>
            <p class="h-about-avail__status">{{ \App\mh_availability_label($gh, \App\field('home_avail_status', __('Open for work', 'sage'))) }}</p>
            <ul class="h-about-avail__types">
              <li>
                <span class="h-about-avail__check" aria-hidden="true">✓</span>
                Full-time roles
              </li>
              <li>
                <span class="h-about-avail__check" aria-hidden="true">✓</span>
                Part-time &amp; contract
              </li>
              <li>
                <span class="h-about-avail__check" aria-hidden="true">✓</span>
                Freelance &amp; project work
              </li>
              <li>
                <span class="h-about-avail__check" aria-hidden="true">✓</span>
                Agency overflow &amp; subs
              </li>
            </ul>
            <a class="btn" href="{{ home_url('/contact/') }}" style="width:100%;justify-content:center;margin-top:.25rem">
              {!! \App\mh_svg_icon('mail', 16) !!} Say hello
            </a>
          </div>
        @endif

        {{-- Who's welcome --}}
        <div class="h-about-who">
          <p class="h-about-who__label">You're welcome here if you're a…</p>
          <ul class="h-about-who__list">
            <li>
              <span class="h-about-who__icon">{!! \App\mh_svg_icon('briefcase', 16) !!}</span>
              <span>
                <strong>Recruiter or hiring manager</strong> — happy to chat about full-time, part-time, or contract roles. WordPress, PHP, and web app work is the sweet spot.
              </span>
            </li>
            <li>
              <span class="h-about-who__icon">{!! \App\mh_svg_icon('users', 16) !!}</span>
              <span>
                <strong>Agency or studio</strong> — I've worked as a silent sub on client projects before. You keep the relationship; I stay in the background.
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

{{-- ═══════════════════════════════════════════════════
     03 — SKILLS (grouped icon grid)
     ═══════════════════════════════════════════════════ --}}
<section class="h-skills" id="skills" aria-labelledby="h-skills-heading">
  <div class="container wide">
    <div class="h-skills__head">
      <div>
        <p class="h-section-label">Skills &amp; tools</p>
        <h2 id="h-skills-heading" class="h-section__title">{{ \App\field('home_build_h2', __('What I work with', 'sage')) }}</h2>
      </div>
      <p class="h-skills__note">Tools I reach for on real projects. Not an exhaustive list.</p>
    </div>

    <div class="h-skill-groups">
      @foreach ($skillGroups as $groupName => $groupSkills)
        <div class="h-skill-group">
          <p class="h-skill-group__label">{{ $groupName }}</p>
          <div class="h-skill-group__tiles">
            @foreach ($groupSkills as [$label, $icon, $color])
              <div class="h-skill-tile" title="{{ $label }}">
                <span class="h-skill-tile__icon" style="--skill-color: {{ $color }}">
                  {!! \App\mh_svg_icon($icon, 26) !!}
                </span>
                <span class="h-skill-tile__name">{{ $label }}</span>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     04 — HOW A PROJECT GOES
     ═══════════════════════════════════════════════════ --}}
<section class="h-process" id="process" aria-labelledby="h-process-heading">
  <div class="container wide">
    <div class="h-section-shell">
      <div class="h-process__head">
        <div>
          <p class="h-section-label">Process</p>
          <h2 id="h-process-heading" class="h-section__title">{{ \App\field('home_process_h2', __('How a project goes.', 'sage')) }}</h2>
          <p class="h-process__subhead">Four steps. Written scope. You own everything at the end.</p>
        </div>
        <a class="h-text-arrow" href="{{ home_url('/services/') }}">Full services →</a>
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

    @include('partials.discovery-cta', [
      'title' => __('Start a project brief.', 'sage'),
      'body'  => __('A rough idea of who the site is for, what it needs to do, and what success looks like — in four short steps. I use it to prepare for our first meeting. No wireframe required.', 'sage'),
      'cta'   => __('Start a brief', 'sage'),
    ])
  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     04b — GOOD FIT / NOT A FIT
     ═══════════════════════════════════════════════════ --}}
<section class="h-fit" id="fit" aria-labelledby="h-fit-heading">
  <div class="container wide">
    <div class="h-section-shell">
      <div class="h-fit__head">
        <p class="h-section-label">Honest expectations</p>
        <h2 id="h-fit-heading" class="h-section__title">Is this a good fit?</h2>
        <p class="h-fit__intro">Most conversations start with the wrong question ("how much does a website cost?"). These answers might save us both time.</p>
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
      <div class="h-fit__cta">
        <div class="h-fit__cta-copy">
          <p class="h-fit__cta-lead">Still not sure?</p>
          <p class="h-fit__cta-note">Write a short note — the worst I can say is I’m not the right person, and I’ll try to point you toward someone who is.</p>
        </div>
        <a class="btn h-fit__cta-btn" href="{{ home_url('/contact/') }}">
          {!! \App\mh_svg_icon('mail', 16) !!}
          Write a note
        </a>
      </div>
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     05 — VALUES / PRINCIPLES
     ═══════════════════════════════════════════════════ --}}
<section class="h-principles" id="principles" aria-labelledby="h-principles-heading">
  <div class="container wide">

    <div class="h-section-shell h-principles__shell">
      <div class="h-principles__head">
        <div>
          <p class="h-section-label">How I work</p>
          <h2 id="h-principles-heading" class="h-section__title">
            How I ship WordPress platforms shops can keep.
          </h2>
          <p class="h-principles__intro">
            Sage 11, Blade, Tailwind, Vite, and PHP 8.3 — deployed through GitHub. Here is how I run a build from the first note to a clean handoff.
          </p>
        </div>
      </div>

      <div class="h-principles__grid">
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
                <span class="h-principle__practice-label">In practice</span>
                <p class="h-principle__practice-text">{{ $v['practice'] }}</p>
              </div>
            @endif
          </article>
        @endforeach
      </div>

      <div class="h-principles__cta">
        <div class="h-principles__cta-copy">
          <p class="h-principles__cta-lead">These are how I actually ship — not a manifesto.</p>
          <p class="h-principles__cta-note">If they sound like a fit, say hello. I usually reply within a day.</p>
        </div>
        <a class="btn h-principles__cta-btn" href="{{ home_url('/contact/') }}">
          {!! \App\mh_svg_icon('mail', 16) !!}
          Say hello
        </a>
      </div>
    </div>

  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     06 — SELECTED WORK
     ═══════════════════════════════════════════════════ --}}
@if (! empty($work))
@php
  $totalProjects = count(\App\mh_work_page_items());
  $featuredWork  = $work[0] ?? null;
  $remainingWork = array_slice($work, 1, 3);
@endphp
<section class="h-section h-section--tinted" id="work" aria-labelledby="h-work-heading">
  <div class="container wide">

    {{-- Section header: SEO-rich heading + count + link --}}
    <div class="h-work-header">
      <div>
        <p class="h-section-label">Projects</p>
        <h2 id="h-work-heading" class="h-section__title">
          {{ \App\field('home_work_h2', __('Example WordPress sites for shops, tours, and inns.', 'sage')) }}
        </h2>
        <p class="h-work-intro">
          {{ \App\field('home_work_intro', __('I publish example WordPress sites here — live demos for shops, tours, and inns. Hire me on this site for a real build.', 'sage')) }}
        </p>
      </div>
      <div class="h-work-header__meta">
        <span class="h-work-count">{{ $totalProjects }} projects</span>
        <a class="h-text-arrow" href="{{ home_url('/projects/') }}">Browse all →</a>
      </div>
    </div>

    {{-- Featured project — full-width image card with overlay --}}
    @if ($featuredWork)
    @php $fp = $featuredWork; @endphp
    <article class="h-work-featured" aria-label="{{ esc_attr($fp['title'] ?? '') }}">
      @if (! empty($fp['image']))
        <div class="h-work-featured__img">
          <img
            src="{{ esc_url($fp['image']) }}"
            alt="{{ esc_attr($fp['title']) }} — {{ esc_attr($fp['cat']) }} website project for {{ esc_attr($fp['place']) }}"
            width="1200"
            height="630"
            loading="eager"
            decoding="async"
          >
        </div>
      @else
        <div class="h-work-featured__img h-work-featured__img--text">
          <span>{{ $fp['title'] }}</span>
        </div>
      @endif

      <div class="h-work-featured__overlay">
        <div class="h-work-featured__meta">
          <span class="h-work-cat-badge">{{ $fp['cat'] }}</span>
          <span class="h-work-place">{!! \App\mh_svg_icon('map', 13) !!} {{ $fp['place'] }}</span>
        </div>
        <h3 class="h-work-featured__title">
          <a href="{{ esc_url($fp['url'] ?? \App\mh_concept_page_url((string) ($fp['slug'] ?? ''))) }}">{{ $fp['title'] }}</a>
        </h3>
        <p class="h-work-featured__blurb">{{ $fp['blurb'] }}</p>
        <div class="h-work-featured__actions">
          <a class="btn btn-on-dark h-work-btn" href="{{ esc_url($fp['url'] ?? \App\mh_concept_page_url((string) ($fp['slug'] ?? ''))) }}">
            View project
          </a>
          <a class="h-work-ghost-link" href="{{ home_url('/projects/') }}?cat={{ rawurlencode((string) ($fp['cat'] ?? '')) }}">
            See all {{ strtolower($fp['cat']) }} projects →
          </a>
        </div>
        @if (! empty($fp['tech']))
          <div class="h-work-featured__pills">
            @foreach (array_slice($fp['tech'], 0, 5) as $t)
              <span class="h-work-pill">{!! \App\mh_svg_icon($t, 13) !!} {{ $t }}</span>
            @endforeach
          </div>
        @endif
      </div>
    </article>
    @endif

    {{-- Remaining 3 cards — clean uniform grid --}}
    @if (! empty($remainingWork))
    <div class="h-work-grid">
      @foreach ($remainingWork as $p)
        <article class="h-work-card-v2">

          {{-- Image --}}
          @php
            $cardHref = esc_url($p['url'] ?? \App\mh_concept_page_url((string) ($p['slug'] ?? '')));
          @endphp
          <a class="h-work-card-v2__imglink" href="{{ $cardHref }}" aria-label="View {{ esc_attr($p['title']) }} project">
            @if (! empty($p['image']))
              <div class="h-work-card-v2__img">
                <img
                  src="{{ esc_url($p['image']) }}"
                  alt="{{ esc_attr($p['title']) }} — {{ esc_attr($p['cat']) }} website project, {{ esc_attr($p['place']) }}"
                  width="640"
                  height="360"
                  loading="lazy"
                  decoding="async"
                >
              </div>
            @else
              <div class="h-work-card-v2__img h-work-card-v2__img--text">
                {{ $p['title'] }}
              </div>
            @endif
          </a>

          {{-- Content --}}
          <div class="h-work-card-v2__body">
            <div class="h-work-card-v2__top">
              <span class="h-work-cat-badge h-work-cat-badge--sm">{{ $p['cat'] }}</span>
              <span class="h-work-place h-work-place--sm">{!! \App\mh_svg_icon('map', 12) !!} {{ $p['place'] }}</span>
            </div>
            <h3 class="h-work-card-v2__title">
              <a href="{{ $cardHref }}">{{ $p['title'] }}</a>
            </h3>
            <p class="h-work-card-v2__blurb">{{ $p['blurb'] }}</p>
            @if (! empty($p['tech']))
              <div class="pill-row h-work-card-v2__pills">
                @foreach (array_slice($p['tech'], 0, 3) as $t)
                  <span class="pill">{!! \App\mh_svg_icon($t, 12) !!} {{ $t }}</span>
                @endforeach
              </div>
            @endif
            <div class="h-work-card-v2__links">
              <a class="h-work-cta-link" href="{{ $cardHref }}">
                View project →
              </a>
            </div>
          </div>

        </article>
      @endforeach
    </div>
    @endif

    {{-- Bottom CTA bar --}}
    <div class="h-work-cta-bar">
      <p>Projects for tours, inns, shops, restaurants, and real estate agencies.</p>
      <a class="btn" href="{{ home_url('/projects/') }}">Browse all {{ $totalProjects }} projects</a>
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
        <p class="h-section-label">Open source</p>
        <h2 id="h-oss-heading" class="h-section__title">{{ __('Code you can use.', 'sage') }}</h2>
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
            @foreach (array_slice($ossData['events'], 0, 5) as $ev)
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

    {{-- Repo cards with live stats --}}
    @if (! empty($ossData['repos']))
      <div class="h-oss-grid">
        @foreach ($ossData['repos'] as $r)
          <article class="h-oss-card">

            {{-- Header: name + activity badge --}}
            <div class="h-oss-card__head">
              <span class="h-oss-card__icon" aria-hidden="true">{!! \App\mh_svg_icon('github', 18) !!}</span>
              <h3 class="h-oss-card__name">
                <a href="{{ esc_url($r['url']) }}" rel="noopener" target="_blank">
                  {{ $r['display_name'] ?? \App\mh_title_label($r['name']) }}<span class="visually-hidden"> (opens in a new window)</span>
                </a>
              </h3>
              @if (! empty($r['badge']))
                <span class="h-oss-badge {{ $r['badge_class'] }}">{{ $r['badge'] }}</span>
              @endif
            </div>

            {{-- Description --}}
            <p class="h-oss-card__desc">{{ $r['desc'] }}</p>

            {{-- Live stats row --}}
            <div class="h-oss-card__stats">
              @if ($r['stars'] > 0)
                <span class="h-oss-stat">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 8.7H24l-7.5 5.5 2.9 8.8L12 19.4l-7.4 5.6 2.9-8.8L0 10.7h9.1z"/></svg>
                  {{ number_format_i18n($r['stars']) }}
                </span>
              @endif
              @if ($r['forks'] > 0)
                <span class="h-oss-stat">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 3a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM7 5h.01M17 5h.01M7 9v3a1 1 0 0 0 1 1h3a1 1 0 0 1 1 1v3m1-8h.01M12 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/></svg>
                  {{ number_format_i18n($r['forks']) }}
                </span>
              @endif
              @if ($r['lang'])
                <span class="h-oss-stat">
                  {!! \App\mh_svg_icon($r['lang'], 14) !!} {{ $r['lang'] }}
                </span>
              @endif
              @if ($r['pushed_ago'])
                <span class="h-oss-stat h-oss-stat--muted">Updated {{ $r['pushed_ago'] }}</span>
              @endif
            </div>

            {{-- Health / activity score bar --}}
            @if (! empty($r['health']))
              <div class="h-oss-card__health">
                <span class="h-oss-card__health-label">Activity score</span>
                <div class="h-oss-health-bar" role="progressbar" aria-valuenow="{{ $r['health'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $r['health'] }}/100">
                  <div class="h-oss-health-bar__fill" style="width: {{ $r['health'] }}%"></div>
                </div>
                <span class="h-oss-card__health-score">{{ $r['health'] }}<span aria-hidden="true">/100</span></span>
              </div>
            @endif

            {{-- Language breakdown bars --}}
            @if (! empty($r['lang_bars']))
              <div class="h-lang-bars" aria-label="Language breakdown">
                <div class="h-lang-bars__track">
                  @foreach ($r['lang_bars'] as $lb)
                    @php
                      $langColors = [
                        'PHP' => '#7a86b8', 'JavaScript' => '#f7df1e', 'TypeScript' => '#3178c6',
                        'CSS' => '#563d7c', 'HTML' => '#e34c26', 'Blade' => '#e3342f',
                        'Shell' => '#89e051', 'Python' => '#3572a5', 'Ruby' => '#701516',
                      ];
                      $lc = $langColors[$lb['lang']] ?? '#6b7280';
                    @endphp
                    <span class="h-lang-bar" style="width:{{ $lb['pct'] }}%; background:{{ $lc }}" title="{{ $lb['lang'] }}: {{ $lb['pct'] }}%"></span>
                  @endforeach
                </div>
                <div class="h-lang-bars__labels">
                  @foreach (array_slice($r['lang_bars'], 0, 3) as $lb)
                    @php $lc = $langColors[$lb['lang']] ?? '#6b7280'; @endphp
                    <span class="h-lang-label" style="--lang-color:{{ $lc }}">{{ $lb['lang'] }} {{ $lb['pct'] }}%</span>
                  @endforeach
                </div>
              </div>
            @endif

            {{-- Tags --}}
            @if (! empty($r['tags']))
              <div class="pill-row">
                @foreach (array_slice($r['tags'], 0, 4) as $t)
                  <span class="pill">{!! \App\mh_svg_icon($t, 12) !!} {{ $t }}</span>
                @endforeach
              </div>
            @endif

            <a class="h-oss-card__link" href="{{ esc_url($r['url']) }}" rel="noopener" target="_blank">
              {!! \App\mh_svg_icon('code', 15) !!} View on GitHub
            </a>

          </article>
        @endforeach
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
<section class="h-journal" id="journal" aria-labelledby="h-writing-heading">
  <div class="container wide">

    {{-- Header ─ SEO-rich heading + intro + links --}}
    <div class="h-journal__head">
      <div class="h-journal__head-copy">
        <p class="h-section-label">Journal</p>
        <h2 id="h-writing-heading" class="h-section__title">
          {{ \App\field('home_write_h2', __('Full-stack notes, WordPress code, and project lessons.', 'sage')) }}
        </h2>
        <p class="h-journal__intro">
          {{ \App\field('home_write_intro', __('Practical notes from WordPress, PHP, JavaScript, React, APIs, and real project work. Most posts include code you can adapt or use.', 'sage')) }}
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
            {!! \App\mh_svg_icon('pen', 13) !!} Latest post
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
            <article class="h-journal__post" itemscope itemtype="https://schema.org/BlogPosting">
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

          {{-- Stack footer --}}
          <div class="h-journal__stack-footer">
            <a class="h-text-arrow" href="{{ $writing }}">Browse all posts →</a>
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
<section class="h-faq" id="faq" aria-labelledby="h-faq-heading">
  <div class="container wide h-faq__inner">

    <div class="h-faq__sidebar">
      <p class="h-section-label">Questions</p>
      <h2 id="h-faq-heading" class="h-section__title">Frequently asked.</h2>
      <p class="h-faq__blurb">Real questions from real conversations. If yours isn't here, <a href="{{ home_url('/contact/') }}">just ask</a>.</p>
      <div class="h-avail-card">
        <p class="h-avail-card__label">
          <span class="h-badge__dot" aria-hidden="true"></span>
          Current availability
        </p>
        <p class="h-avail-card__status">{{ \App\field('home_avail_status', __('Open to new projects', 'sage')) }}</p>
        <ul class="h-avail-card__details">
          <li>{!! \App\mh_svg_icon('clock', 13) !!} Eastern Time</li>
          <li>{!! \App\mh_svg_icon('calendar', 13) !!} Replies within 24 hours</li>
          <li>{!! \App\mh_svg_icon('code', 13) !!} Project-based work</li>
        </ul>
        <a class="btn" href="{{ home_url('/contact/') }}" style="width:100%;justify-content:center;margin-top:.25rem">
          {!! \App\mh_svg_icon('mail', 16) !!} Say hello
        </a>
      </div>
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
      <h2 id="h-cta-heading" class="display-title is-section h-cta__heading">{{ \App\field('home_help_h2', __('Working on something?', 'sage')) }}</h2>
      <p class="h-cta__body">{{ \App\field('home_help_p2', __('A question about a post is just as welcome as a project. I usually reply within a day.', 'sage')) }}</p>
    </div>
    <div class="cta-band__actions h-cta__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 17) !!}
        {{ \App\field('home_link_hello', __('Say hello', 'sage')) }}
      </a>
      <a class="btn btn-ghost" href="{{ home_url('/hire/') }}">{{ __('Hire me', 'sage') }}</a>
      <p class="cta-band__note">{{ __('Remote · usually within a day', 'sage') }}</p>
    </div>
  </div>
</section>
