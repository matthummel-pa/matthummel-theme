@php
  $posts   = \App\mh_latest_posts(4);
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
    ['Notion',        'notion'],
    ['Google Drive',  'google-drive'],
    ['HubSpot',       'hubspot'],
    ['Rank Math SEO', 'rank-math'],
    ['Power Apps',    'power-apps'],
    ['Power Automate','power-automate'],
    ['MySQL',         'database'],
    ['VS Code',       'vscode'],
    ['Node.js',       'nodejs'],
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
    ],
    'AI & Tooling' => [
      ['Cursor AI',   'cursor-ai',  '#111827'],
      ['Claude',      'claude',     '#d97706'],
      ['ChatGPT',     'chatgpt',    '#10a37f'],
    ],
    'Workflow'   => [
      ['Notion',      'notion',     '#000000'],
      ['Google Drive','google-drive','#4285f4'],
    ],
    'Marketing'  => [
      ['HubSpot',     'hubspot',    '#ff7a59'],
      ['Rank Math SEO','rank-math', '#f50c24'],
    ],
    'Platform'   => [
      ['Power Apps',  'power-apps', '#742774'],
      ['Power Automate','power-automate','#0066ff'],
    ],
  ];

  $values = [
    ['Shops should own their content — not rent it.', 'Hosting, domain, and database belong to the client. Always.'],
    ['Fast delivery doesn\'t mean skipping the review.', 'I use AI tools to speed up the repetitive parts. Every line still gets read, tested, and understood before it ships. Quicker turnaround, same quality bar.'],
    ['Code is documentation. Write it clearly.', 'If a developer can\'t read it in six months, it was written for the machine, not the team.'],
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
      'body'   => 'I send a plain list of work, a rough timeline, a fixed price, and an explicit list of what\'s out of scope. You approve or push back.',
      'timing' => '2–4 days',
      'gets'   => ['Fixed price, not hourly', 'Clear out-of-scope list', 'No retainers or surprise invoices'],
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
      'You need a WordPress site you can actually update yourself',
      'You want a quick turnaround without cutting corners on quality',
      'You want clean code a future developer can read',
      'You have a clear idea of what you need — or want help figuring it out',
      'You prefer a fixed price over hourly billing',
      'You\'re a shop, agency, or developer who needs a reliable sub',
    ],
    'no'  => [
      'You need a designer — I\'m a developer (I can refer you to one)',
      'You need a site in under a week',
      'You want ongoing social media or ad management',
      'You need an enterprise e-commerce platform from scratch',
      'You\'re looking for the lowest possible price, not the best outcome',
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
      'q' => 'What about Power Platform — do you still take that work?',
      'a' => 'Sometimes, when a team already lives in Microsoft 365 and it\'s the right tool. It\'s not my main focus and I\'ll say so if WordPress or another stack is a better fit. I won\'t talk you into it.',
    ],
  ];
@endphp

{{-- ═══════════════════════════════════════════════════
     01 — HERO
     ═══════════════════════════════════════════════════ --}}
<section class="h-hero" aria-labelledby="h-hero-name">
  <div class="container wide h-hero__inner">

    <div class="h-hero__copy">

      <div class="h-hero__badges">
        <span class="h-badge">
          {!! \App\mh_svg_icon('map', 13) !!}
          {{ \App\field('home_kicker', $gh['location'] ?: __('Gettysburg, PA', 'sage')) }}
        </span>
        @if (! empty($gh['hireable']))
          <span class="h-badge h-badge--open">
            <span class="h-badge__dot" aria-hidden="true"></span>
            Available for work
          </span>
        @endif
        <span class="h-badge">
          {!! \App\mh_svg_icon('code', 13) !!}
          WordPress developer
        </span>
      </div>

      <h1 id="h-hero-name" class="h-hero__name">
        {{ \App\field('home_h1', $gh['name'] ?: __('Matt Hummel', 'sage')) }}
      </h1>

      <p class="h-hero__role">
        {{ \App\field('home_role', __('I build WordPress sites and plugins.', 'sage')) }}
      </p>

      <p class="h-hero__lede">
        {{ \App\field('home_lede', __('Based in Gettysburg, PA. Shops get something they actually own — not a subscription they rent. Developers get code they can read.', 'sage')) }}
      </p>

      <div class="h-hero__actions">
        <a class="btn h-hero__cta" href="{{ esc_url(\App\field_href('home_cta_primary_url', '/contact/')) }}">
          {!! \App\mh_svg_icon('mail', 17) !!}
          {{ \App\field('home_cta_primary', __('Say hello', 'sage')) }}
        </a>
        <a class="h-text-arrow" href="{{ esc_url(\App\field_href('home_cta_secondary_url', '/projects/')) }}">
          {{ \App\field('home_cta_secondary', __('See my work', 'sage')) }}
          <span aria-hidden="true">→</span>
        </a>
      </div>

      <dl class="h-stats">
        @if (! empty($gh['public_repos']))
          <div>
            <dt><a href="{{ esc_url($ghUrl.'?tab=repositories') }}" rel="me noopener" target="_blank">{{ number_format_i18n((int) $gh['public_repos']) }}</a></dt>
            <dd>public repos</dd>
          </div>
        @endif
        @if (! empty($gh['followers']))
          <div>
            <dt><a href="{{ esc_url($ghUrl.'?tab=followers') }}" rel="me noopener" target="_blank">{{ number_format_i18n((int) $gh['followers']) }}</a></dt>
            <dd>GitHub followers</dd>
          </div>
        @endif
        <div>
          <dt>Gettysburg</dt>
          <dd>Pennsylvania</dd>
        </div>
        <div>
          <dt>WordPress</dt>
          <dd>primary stack</dd>
        </div>
      </dl>

      <nav class="h-quick" aria-label="{{ __('Quick links', 'sage') }}">
        <a href="{{ $writing }}">{!! \App\mh_svg_icon('pen', 14) !!} Journal</a>
        <a href="{{ home_url('/code/') }}">{!! \App\mh_svg_icon('code', 14) !!} Code</a>
        <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">{!! \App\mh_svg_icon('github', 14) !!} GitHub</a>
        <a href="{{ home_url('/about/') }}">{!! \App\mh_svg_icon('user', 14) !!} About</a>
        <a href="{{ home_url('/now/') }}">{!! \App\mh_svg_icon('calendar', 14) !!} Now</a>
      </nav>

    </div>

    <div class="h-hero__photo-wrap" aria-hidden="true">
      @include('partials.profile-photo', [
        'size'       => 400,
        'class'      => 'profile-photo h-hero__photo',
        'eager'      => true,
        'decorative' => true,
      ])
      <div class="h-hero__photo-ring"></div>
    </div>

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
<section class="h-about" aria-labelledby="h-about-heading">
  <div class="container wide h-about__inner">

    <div class="h-about__aside">
      @include('partials.profile-photo', [
        'size'       => 220,
        'class'      => 'profile-photo h-about__img',
        'eager'      => false,
        'decorative' => true,
      ])
      <div class="h-about__meta">
        <span class="h-meta-item">{!! \App\mh_svg_icon('map', 14) !!} Gettysburg, PA</span>
        <span class="h-meta-item">{!! \App\mh_svg_icon('code', 14) !!} Full-stack developer</span>
        <span class="h-meta-item">{!! \App\mh_svg_icon('github', 14) !!} @matthummel-pa</span>
      </div>
    </div>

    <div class="h-about__body">
      <p class="h-section-label">About me</p>
      <h2 id="h-about-heading" class="h-about__heading">
        {{ \App\field('home_about_h2', __('Developer first. Gettysburg always.', 'sage')) }}
      </h2>
      <p class="h-about__text">
        {{ \App\field('home_about_text', __('I\'ve been building for the web since higher-ed marketing days. WordPress stuck because it gives shops real ownership — they can edit their own pages without calling me. I still do Power Platform work when a team lives in Microsoft 365, but WordPress is what I enjoy most.', 'sage')) }}
      </p>
      <p class="h-about__text">
        {{ \App\field('home_about_p2', __('Most of my public code is on GitHub. Snippets go on the journal. If something helped you, you don\'t need to ask permission to use it.', 'sage')) }}
      </p>
      <div class="h-about__links">
        <a class="h-text-arrow" href="{{ home_url('/about/') }}">More about me →</a>
        <a class="h-text-arrow" href="{{ home_url('/now/') }}">What I\'m doing now →</a>
      </div>
    </div>

  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     03 — SKILLS (grouped icon grid)
     ═══════════════════════════════════════════════════ --}}
<section class="h-skills" aria-labelledby="h-skills-heading">
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
<section class="h-process" aria-labelledby="h-process-heading">
  <div class="container wide">
    <div class="h-process__head">
      <div>
        <p class="h-section-label">Process</p>
        <h2 id="h-process-heading" class="h-section__title">{{ \App\field('home_process_h2', __('How a project goes.', 'sage')) }}</h2>
        <p class="h-process__subhead">Four steps. Fixed price. You own everything at the end.</p>
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

    {{-- What I need callout --}}
    <div class="h-need-callout">
      <div class="h-need-callout__icon" aria-hidden="true">{!! \App\mh_svg_icon('user', 22) !!}</div>
      <div>
        <p class="h-need-callout__label">What I need from you</p>
        <p class="h-need-callout__body">A rough idea of who the site is for, what it needs to do, and what success looks like. No spec or wireframe required — a few sentences work. Modern tooling means I can turn a clear brief into a working preview faster than a traditional build. The clearer the brief, the faster everything moves.</p>
      </div>
      <a class="btn" href="{{ home_url('/contact/') }}" style="flex-shrink:0">
        {!! \App\mh_svg_icon('mail', 16) !!} Write a note
      </a>
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     04b — GOOD FIT / NOT A FIT
     ═══════════════════════════════════════════════════ --}}
<section class="h-fit" aria-labelledby="h-fit-heading">
  <div class="container wide">
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
    <p class="h-fit__footer">Still not sure? <a href="{{ home_url('/contact/') }}">Write a note</a> — the worst I can say is I'm not the right person, and I'll try to point you toward someone who is.</p>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     05 — VALUES
     ═══════════════════════════════════════════════════ --}}
<section class="h-values" aria-label="{{ __('Principles', 'sage') }}">
  <div class="container wide">
    <p class="h-section-label" style="margin-bottom:2rem">How I think about the work</p>
    <div class="h-values__grid">
      @foreach ($values as [$headline, $detail])
        <div class="h-value">
          <p class="h-value__headline">{{ $headline }}</p>
          <p class="h-value__detail">{{ $detail }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     06 — SELECTED WORK (bento)
     ═══════════════════════════════════════════════════ --}}
@if (! empty($work))
<section class="h-section h-section--tinted" aria-labelledby="h-work-heading">
  <div class="container wide">
    <div class="h-section__head">
      <div>
        <p class="h-section-label">Projects</p>
        <h2 id="h-work-heading" class="h-section__title">{{ \App\field('home_work_h2', __('Selected work', 'sage')) }}</h2>
      </div>
      <a class="h-text-arrow" href="{{ home_url('/projects/') }}">All projects →</a>
    </div>

    <div class="h-bento">
      @foreach ($work as $i => $p)
        <article class="h-bento__card{{ $i === 0 ? ' h-bento__card--featured' : '' }}">
          <a class="h-bento__link" href="{{ home_url('/projects/') }}#{{ $p['slug'] }}" aria-label="{{ esc_attr($p['title']) }}"></a>
          @if (! empty($p['image']))
            <div class="h-bento__visual">
              <img src="{{ esc_url($p['image']) }}" alt="{{ esc_attr($p['title']) }}" width="{{ $i === 0 ? 900 : 540 }}" height="{{ $i === 0 ? 520 : 300 }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" decoding="async">
            </div>
          @else
            <div class="h-bento__visual h-bento__visual--text">
              <span>{{ $p['title'] }}</span>
            </div>
          @endif
          <div class="h-bento__body">
            <p class="h-label">{{ $p['cat'] }} · {{ $p['place'] }}</p>
            <h3><a href="{{ home_url('/projects/') }}#{{ $p['slug'] }}">{{ $p['title'] }}</a></h3>
            <p class="h-bento__blurb">{{ $p['blurb'] }}</p>
            @if (! empty($p['tech']))
              <div class="pill-row h-bento__pills">
                @foreach (array_slice($p['tech'], 0, 4) as $t)
                  <span class="pill">{!! \App\mh_svg_icon($t, 12) !!} {{ $t }}</span>
                @endforeach
              </div>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════
     07 — OPEN SOURCE  (live GitHub API data)
     ═══════════════════════════════════════════════════ --}}
<section class="h-section" aria-labelledby="h-oss-heading">
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
<section class="h-section h-section--tinted" aria-labelledby="h-writing-heading">
  <div class="container wide">
    <div class="h-section__head">
      <div>
        <p class="h-section-label">Journal</p>
        <h2 id="h-writing-heading" class="h-section__title">{{ \App\field('home_write_h2', __('From the journal', 'sage')) }}</h2>
      </div>
      <a class="h-text-arrow" href="{{ $writing }}">All posts →</a>
    </div>

    @if (! empty($posts))
      @php($featured = $posts[0])
      <article class="h-post-featured">
        @if (! empty($featured['thumb']))
          <div class="h-post-featured__visual">
            <img src="{{ esc_url($featured['thumb']) }}" alt="{{ esc_attr($featured['title']) }}" width="960" height="540" loading="lazy" decoding="async">
          </div>
        @else
          <div class="h-post-featured__visual h-post-featured__visual--text">
            <span>{{ wp_trim_words($featured['title'], 6, '') }}</span>
          </div>
        @endif
        <div class="h-post-featured__body">
          <div class="h-post-featured__meta">
            @if ($featured['cat'])<span class="h-post-cat">{{ $featured['cat'] }}</span>@endif
            <span class="h-post-date">{{ $featured['date'] }}</span>
            @if (! empty($featured['minutes']))<span class="h-post-min">{{ $featured['minutes'] }} min read</span>@endif
          </div>
          <h3 class="h-post-featured__title">
            <a href="{{ esc_url($featured['url']) }}">{{ $featured['title'] }}</a>
          </h3>
          <p class="h-post-featured__ex">{{ $featured['ex'] }}</p>
          <a class="h-post-featured__link" href="{{ esc_url($featured['url']) }}">
            Read post <span aria-hidden="true">→</span>
          </a>
        </div>
      </article>

      @if (count($posts) > 1)
        <div class="h-post-cards">
          @foreach (array_slice($posts, 1) as $post)
            <article class="h-post-card">
              @if (! empty($post['thumb']))
                <div class="h-post-card__visual">
                  <img src="{{ esc_url($post['thumb']) }}" alt="{{ esc_attr($post['title']) }}" width="640" height="360" loading="lazy" decoding="async">
                </div>
              @else
                <div class="h-post-card__visual h-post-card__visual--text">
                  <span>{{ wp_trim_words($post['title'], 4, '') }}</span>
                </div>
              @endif
              <div class="h-post-card__body">
                <div class="h-post-card__meta">
                  @if ($post['cat'])<span class="h-post-cat">{{ $post['cat'] }}</span>@endif
                  <span class="h-post-date">{{ $post['date'] }}</span>
                </div>
                <h3 class="h-post-card__title">
                  <a href="{{ esc_url($post['url']) }}">{{ $post['title'] }}</a>
                </h3>
                <p class="h-post-card__ex">{{ $post['ex'] }}</p>
              </div>
            </article>
          @endforeach
        </div>
      @endif
    @else
      <p>{{ \App\field('home_write_empty', __('New posts coming soon.', 'sage')) }}</p>
    @endif
  </div>
</section>

{{-- ═══════════════════════════════════════════════════
     09 — FAQ
     ═══════════════════════════════════════════════════ --}}
<section class="h-faq" aria-labelledby="h-faq-heading">
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
          <li>{!! \App\mh_svg_icon('map', 13) !!} Gettysburg, PA (EST)</li>
          <li>{!! \App\mh_svg_icon('calendar', 13) !!} Replies within 24 hours</li>
          <li>{!! \App\mh_svg_icon('code', 13) !!} Fixed-price projects</li>
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
<section class="h-cta" aria-labelledby="h-cta-heading">
  <div class="container wide h-cta__inner">
    <div>
      <p class="h-section-label" style="color:#60a5fa;margin-bottom:.75rem">Get in touch</p>
      <h2 id="h-cta-heading" class="h-cta__heading">{{ \App\field('home_help_h2', __('Working on something?', 'sage')) }}</h2>
      <p class="h-cta__body">{!! \App\field_html('home_help_p2', __('A question about a post is just as welcome as a project. I usually reply within a day.', 'sage')) !!}</p>
    </div>
    <div class="h-cta__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 17) !!}
        {{ \App\field('home_link_hello', __('Say hello', 'sage')) }}
      </a>
      <a class="h-cta__sub" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
        {!! \App\mh_svg_icon('github', 16) !!} GitHub →
      </a>
    </div>
  </div>
</section>
