{{--
  Template Name: About
--}}
@extends('layouts.app')

@php
  $gh           = \App\Github::fetchUser(\App\mh_github_login());
  $ghUrl        = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $writing      = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $latestPosts  = \App\mh_latest_posts(3);
  $yearsBuilding = \App\mh_years_in_house();
  $services     = \App\mh_about_page_services();
  $workTypes    = \App\mh_about_page_work_types();
  $approach     = \App\mh_about_page_approach();
  $isHireable   = \App\mh_is_hireable($gh);
  $login        = \App\mh_github_login();
@endphp

@section('content')

{{-- HERO (above the fold: name, headline, short lede, CTAs, photo panel) --}}
@component('partials.page-hero', ['extra' => 'about-hero', 'split' => true, 'asideLabel' => __('Quick facts', 'sage')])
  <div class="about-hero__copy">
    <p class="eyebrow">{{ \App\field('about_kicker', __('Matt Hummel', 'sage')) }}</p>
    <h1 class="display-title is-hero">
      {{ \App\field('about_h1', __('WordPress developer for shops and agencies.', 'sage')) }}
    </h1>
    <p class="lead about-hero__lede">
        {{ \App\field('about_lede', __('I build accessible WordPress sites and web apps from Gettysburg — editable in wp-admin, handoff-ready for agencies, and readable for the next developer.', 'sage')) }}
    </p>
    @if ($isHireable)
      <p class="hire-avail about-hero__avail">
        @include('partials.avail-mark', ['gh' => $gh])
        {{ \App\mh_availability_label($gh, __('Open for new work', 'sage')) }}
      </p>
    @endif
    <div class="page-header-split__actions about-hero__actions">
      <a class="btn" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ __('Say hello', 'sage') }}
      </a>
      <a class="btn btn-outline" href="{{ home_url('/hire/') }}">
        {{ __('Hire me', 'sage') }}
      </a>
      <a class="about-hero__ghost" href="#story">{{ __('Read my story', 'sage') }}</a>
    </div>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/about',
      'profileSize' => 280,
      'profileCaption' => __('Full-stack · WordPress', 'sage'),
      'stats' => array_values(array_filter([
        ['value' => $yearsBuilding.'+', 'label' => __('years in-house web', 'sage')],
        ! empty($gh['public_repos'])
          ? ['value' => number_format_i18n((int) $gh['public_repos']), 'label' => __('public repos', 'sage'), 'href' => $ghUrl.'?tab=repositories', 'external' => true]
          : null,
        ['value' => __('Full stack', 'sage'), 'label' => __('WordPress specialist', 'sage')],
        ['value' => __('EST', 'sage'), 'label' => __('Remote / on-site', 'sage')],
      ])),
      'link' => [
        'label' => __('View GitHub', 'sage'),
        'href' => $ghUrl,
        'external' => true,
      ],
    ])
  @endslot
@endcomponent

<nav class="about-jump-band" aria-label="{{ __('On this page', 'sage') }}">
  <div class="container wide">
    <div class="about-jump">
      <p class="about-jump__label">{{ __('On this page', 'sage') }}</p>
      <div class="about-jump__track">
        <a href="#story">{!! \App\mh_svg_icon('book-open', 13) !!} {{ __('Story', 'sage') }}</a>
        <a href="#build">{!! \App\mh_svg_icon('wordpress', 13) !!} {{ __('What I build', 'sage') }}</a>
        @if ($isHireable)
          <a href="#availability">{!! \App\mh_svg_icon('briefcase', 13) !!} {{ __('Open for work', 'sage') }}</a>
        @endif
        <a href="#approach">{!! \App\mh_svg_icon('code', 13) !!} {{ __('How I work', 'sage') }}</a>
        @if (! empty($latestPosts))
          <a href="#journal">{!! \App\mh_svg_icon('pen', 13) !!} {{ __('Journal', 'sage') }}</a>
        @endif
        <a href="#elsewhere">{!! \App\mh_svg_icon('globe', 13) !!} {{ __('Elsewhere', 'sage') }}</a>
      </div>
    </div>
  </div>
</nav>

{{-- STORY --}}
<section class="pf-section about-story-sec" id="story" aria-labelledby="about-story-heading">
  <div class="container wide">
    <div class="about-shell about-shell--story">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner about-story">
        <div class="about-story__copy">
          <p class="eyebrow">{{ __('Story', 'sage') }}</p>
          <h2 id="about-story-heading" class="display-title is-section">
            {{ \App\field('about_story_h2', __('How I got here.', 'sage')) }}
          </h2>
          <p class="about-story__p">{{ \App\field('about_p1', __('I started on the web in higher-ed marketing — landing pages, content updates, and figuring out why a page that looked fine still wasn’t getting clicks. That taught me more about what people need than any course or tool.', 'sage')) }}</p>
          <p class="about-story__p">{{ \App\field('about_p2', __('WordPress is the tool I kept coming back to. Most shops need a site they can edit themselves: update hours, add a product, fix a typo, without waiting on a developer. That still matters to me.', 'sage')) }}</p>
          <p class="about-story__p">{{ \App\field('about_p3', __('The Work gallery is concept sites — public Sage 11 examples, not my employer portfolio. Production client and in-house work stays private unless a shop asks to be featured.', 'sage')) }}</p>
          <p class="about-story__p">{{ \App\field('about_p4', __('Most production work lived inside employers, so I am now publishing Sage/WordPress work, plugins, and spec builds on GitHub. PowerApps, Power Automate, and InfoPath for federal agencies are on the hire page. There is no public demo.', 'sage')) }}</p>
          <div class="about-story__links">
            <a class="btn" href="{{ home_url('/contact/') }}">
              {!! \App\mh_svg_icon('mail', 16) !!}
              {{ \App\field('about_story_cta', __('Say hello', 'sage')) }}
            </a>
            <a class="about-text-link" href="{{ home_url('/now/') }}">
              {{ \App\field('about_story_now', __('What I\'m doing now', 'sage')) }} →
            </a>
          </div>
        </div>

        <aside class="about-story__aside" aria-label="{{ __('GitHub and studio', 'sage') }}">
          <div class="about-aside-card">
            <div class="about-aside-card__head">
              <span class="about-aside-card__mark" aria-hidden="true">{!! \App\mh_svg_icon('github', 18) !!}</span>
              <div>
                <p class="about-aside-card__name">
                  <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
                    {{ '@'.$login }}
                    <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
                  </a>
                </p>
                @if (! empty($gh['created']))
                  <p class="about-aside-card__meta">{{ sprintf(__('On GitHub since %s', 'sage'), $gh['created']) }}</p>
                @endif
              </div>
            </div>
            @if (! empty($gh['bio']))
              <p class="about-aside-card__bio">{{ $gh['bio'] }}</p>
            @endif
            <ul class="about-aside-card__stats">
              @if (! empty($gh['public_repos']))
                <li>
                  <strong>{{ number_format_i18n($gh['public_repos']) }}</strong>
                  <span>{{ __('public repos', 'sage') }}</span>
                </li>
              @endif
              @if (! empty($gh['followers']))
                <li>
                  <strong>{{ number_format_i18n($gh['followers']) }}</strong>
                  <span>{{ __('followers', 'sage') }}</span>
                </li>
              @endif
            </ul>
            @if ($isHireable)
              <p class="about-aside-avail">
                @include('partials.avail-mark', ['gh' => $gh])
                {{ \App\mh_availability_label($gh, __('Available for hire', 'sage')) }}
              </p>
            @endif
            <a class="about-aside-card__link" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
              {{ __('View GitHub profile', 'sage') }} →
              <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
            </a>
          </div>

          <div class="about-aside-card about-aside-card--studio">
            <p class="about-aside-kicker">{!! \App\mh_svg_icon('globe', 14) !!} {{ __('Concept sites', 'sage') }}</p>
            <h3 class="about-aside-card__title">Matt Hummel</h3>
            <p class="about-aside-card__bio">{{ __('Concept WordPress sites for shops, tours, and inns — not a client gallery. Hire me here for a real build.', 'sage') }}</p>
            <a class="about-aside-card__link" href="{{ home_url('/projects/') }}">
              {{ __('See concept sites', 'sage') }} →
            </a>
          </div>
        </aside>
      </div>
    </div>
  </div>
</section>

{{-- WHAT I BUILD --}}
<section class="pf-section pf-section--alt about-build-sec" id="build" aria-labelledby="about-services-heading">
  <div class="container wide">
    <div class="about-shell about-shell--build">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner">
        <header class="about-shell__head">
          <p class="eyebrow">{{ __('Services', 'sage') }}</p>
          <h2 id="about-services-heading" class="display-title is-section">
            {{ \App\field('about_services_h2', __('What I build.', 'sage')) }}
          </h2>
          <p class="sec-intro">
            {{ \App\field('about_services_intro', __('I work across the stack: accessible interfaces, WordPress and PHP back ends, React applications, APIs, databases, and deployment. WordPress is the specialty, not the limit.', 'sage')) }}
          </p>
        </header>
        <div class="about-services">
          @foreach ($services as $i => $svc)
            <article class="about-svc-card">
              <span class="about-svc-card__n" aria-hidden="true">{{ sprintf('%02d', $i + 1) }}</span>
              <div class="about-svc-card__icon">{!! \App\mh_svg_icon($svc['icon'], 22) !!}</div>
              <h3 class="about-svc-card__title">{{ $svc['title'] }}</h3>
              <p class="about-svc-card__body">{{ $svc['body'] }}</p>
            </article>
          @endforeach
        </div>
        <p class="about-services-note">
          {!! \App\field_html('about_services_note', __('Curious about a specific project type? <a href="/contact/">Write a note</a>.', 'sage')) !!}
        </p>
      </div>
    </div>
  </div>
</section>

{{-- OPEN FOR WORK --}}
@if ($isHireable)
<section class="pf-section about-work-sec" id="availability" aria-labelledby="about-work-heading">
  <div class="container wide">
    <div class="about-shell about-shell--work">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner about-openwork">
        <div class="about-openwork__copy">
          <p class="eyebrow">{{ __('Availability', 'sage') }}</p>
          <h2 id="about-work-heading" class="display-title is-section">
            {{ \App\field('about_work_h2', __('Open for work.', 'sage')) }}
          </h2>
          <p>{{ \App\field('about_work_p1', __('I\'m looking for full-time roles, contract gigs, and freelance projects on matthummel.com. Happy to work remote or on-site.', 'sage')) }}</p>
          <p>{{ \App\field('about_work_p2', __('If you’re hiring a full-stack developer, need an experienced WordPress specialist, want agency overflow support, or have a web project to discuss, send a short note about what you’re working on.', 'sage')) }}</p>
          <a class="btn" href="{{ home_url('/contact/') }}">
            {!! \App\mh_svg_icon('mail', 16) !!}
            {{ \App\field('about_work_cta', __('Start a conversation', 'sage')) }}
          </a>
        </div>
        <ul class="about-openwork__types">
          @foreach ($workTypes as $type)
            <li class="about-work-type">
              <span class="about-work-type__check" aria-hidden="true">{!! \App\mh_svg_icon('check', 14) !!}</span>
              <div>
                <p class="about-work-type__title">{{ $type['title'] }}</p>
                <p class="about-work-type__detail">{{ $type['detail'] }}</p>
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
</section>
@endif

{{-- HOW I WORK --}}
<section class="pf-section {{ $isHireable ? 'pf-section--alt' : '' }} about-approach-sec" id="approach" aria-labelledby="about-approach-heading">
  <div class="container wide">
    <div class="about-shell about-shell--approach">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner">
        <header class="about-shell__head">
          <p class="eyebrow">{{ __('Approach', 'sage') }}</p>
          <h2 id="about-approach-heading" class="display-title is-section">
            {{ \App\field('about_values_h2', __('How I work.', 'sage')) }}
          </h2>
          <p class="sec-intro">
            {{ \App\field('about_values_intro', __('Sage 11, Blade, Tailwind, Vite, and PHP 8.3 — shipped through GitHub. Here is how I run a WordPress build from the first note to a clean handoff.', 'sage')) }}
          </p>
        </header>
        <div class="about-approach">
          @foreach ($approach as $item)
            <article class="about-approach__item">
              <span class="about-approach__icon" aria-hidden="true">{!! \App\mh_svg_icon($item['icon'], 18) !!}</span>
              <div>
                <h3>{{ $item['title'] }}</h3>
                <p>{{ $item['body'] }}</p>
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

{{-- JOURNAL --}}
@if (! empty($latestPosts))
<section class="pf-section about-posts-sec" id="journal" aria-labelledby="about-writing-heading">
  <div class="container wide">
    <div class="about-shell about-shell--posts">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner">
        <header class="about-shell__head about-shell__head--split">
          <div>
            <p class="eyebrow">{{ __('Journal', 'sage') }}</p>
            <h2 id="about-writing-heading" class="display-title is-section">
              {{ \App\field('about_posts_h2', __('Recent posts.', 'sage')) }}
            </h2>
          </div>
          <a class="btn btn-outline about-shell__cta" href="{{ $writing }}">
            {{ \App\field('about_posts_all', __('All posts', 'sage')) }} →
          </a>
        </header>
        <div class="about-posts">
          @foreach ($latestPosts as $post)
            <article class="about-post-row">
              <div class="about-post-row__meta">
                @if ($post['cat'])
                  <span class="about-post-cat">{{ $post['cat'] }}</span>
                @endif
                <time datetime="{{ esc_attr($post['date_iso'] ?? '') }}" class="about-post-date">{{ $post['date'] }}</time>
                @if (! empty($post['minutes']))
                  <span class="about-post-min">{{ sprintf(__('%s min', 'sage'), $post['minutes']) }}</span>
                @endif
              </div>
              <div class="about-post-row__body">
                <h3 class="about-post-row__title">
                  <a href="{{ esc_url($post['url']) }}">{{ $post['title'] }}</a>
                </h3>
                <p class="about-post-row__ex">{{ $post['ex'] }}</p>
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>
@endif

{{-- ELSEWHERE --}}
<section class="pf-section pf-section--alt about-elsewhere-sec" id="elsewhere" aria-labelledby="about-elsewhere-heading">
  <div class="container wide">
    <div class="about-shell about-shell--elsewhere">
      <div class="about-shell__mesh" aria-hidden="true"></div>
      <div class="about-shell__inner about-elsewhere">
        <div>
          <p class="eyebrow">{{ __('Online', 'sage') }}</p>
          <h2 id="about-elsewhere-heading" class="display-title is-section">
            {{ \App\field('about_elsewhere_h2', __('Where to find me.', 'sage')) }}
          </h2>
          <p class="sec-intro">
            {{ \App\field('about_elsewhere_intro', __('Most of my WordPress code and writing shows up here and on GitHub. RSS is the calmest way to follow along.', 'sage')) }}
          </p>
        </div>
        <div class="about-elsewhere__links">
          @include('partials.social', ['labeled' => true])
        </div>
      </div>
    </div>
  </div>
</section>

{{-- CTA --}}
<section class="cta-band about-cta" aria-labelledby="about-cta-heading" data-reveal>
  <div class="container wide cta-band-inner">
    <div class="cta-band__copy about-cta__copy">
      <p class="eyebrow eyebrow--on-dark">{{ \App\field('about_cta_kicker', __('Get in touch', 'sage')) }}</p>
      <h2 id="about-cta-heading" class="display-title is-section">
        {{ \App\field('about_cta_h2', __('Need a full-stack or WordPress development partner?', 'sage')) }}
      </h2>
      <p>{{ \App\field('about_cta_lede', __('Got a question about a post, a project, or a role? Send it over. I usually reply within a day.', 'sage')) }}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ \App\field('about_cta_btn', __('Write a note', 'sage')) }}
      </a>
      <a class="btn btn-ghost" href="{{ home_url('/hire/') }}">{{ __('Hire me', 'sage') }}</a>
      <p class="cta-band__note">{{ __('Remote · usually within a day', 'sage') }}</p>
    </div>
  </div>
</section>

@endsection
