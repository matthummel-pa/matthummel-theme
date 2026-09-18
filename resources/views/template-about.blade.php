{{--
  Template Name: About
--}}
@extends('layouts.app')

@php
  $gh           = \App\Github::fetchUser(\App\mh_github_login());
  $ghUrl        = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $yearsBuilding = \App\mh_years_in_house();
  $services     = \App\mh_about_page_services();
  $workTypes    = \App\mh_about_page_work_types();
  $approach     = \App\mh_about_page_approach();
  $isHireable   = \App\mh_is_hireable($gh);
  $login        = \App\mh_github_login();
@endphp

@section('content')

{{-- HERO (above the fold: name, headline, short lede, CTAs, facts panel) --}}
@component('partials.page-hero', ['extra' => 'about-hero', 'split' => true, 'asideLabel' => __('Quick facts', 'sage')])
  <div class="about-hero__copy">
    <p class="eyebrow">{{ \App\field('about_kicker', __('Matt Hummel', 'sage')) }}</p>
    <h1 class="display-title is-hero">
      {{ \App\field('about_h1', __('WordPress developer for shops and agencies.', 'sage')) }}
    </h1>
    <p class="lead about-hero__lede">
        {{ \App\field('about_lede', __('I build WordPress sites and web apps shops can edit, agencies can hand off, and the next developer can read.', 'sage')) }}
    </p>
    <div class="page-header-split__actions about-hero__actions">
      <a class="btn" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ __('Say hello', 'sage') }}
      </a>
      <a class="h-text-arrow" href="#story">{{ __('Read my story', 'sage') }} <span aria-hidden="true">→</span></a>
    </div>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/about',
      'icon' => 'user',
      'title' => __('Matt Hummel', 'sage'),
      'meta' => __('WordPress · full stack', 'sage'),
      'image' => \App\mh_profile_photo_url(480),
      'imageAlt' => __('Matt Hummel', 'sage'),
      'imageClass' => 'h-hero-illu__photo--square',
      'link' => [
        'label' => __('View GitHub', 'sage'),
        'href' => $ghUrl,
        'external' => true,
      ],
    ])
  @endslot
@endcomponent

@php
  $aboutNav = [
    ['story', __('Story', 'sage')],
    ['build', __('What I build', 'sage')],
  ];
  if ($isHireable) {
    $aboutNav[] = ['availability', __('Open for work', 'sage')];
  }
  $aboutNav[] = ['glance', __('At a glance', 'sage')];
  $aboutNav[] = ['process', __('Process', 'sage')];
  $aboutNav[] = ['fit', __('Fit', 'sage')];
  $aboutNav[] = ['approach', __('How I work', 'sage')];
  $aboutNav[] = ['faq', __('FAQ', 'sage')];
  $aboutNav[] = ['elsewhere', __('Elsewhere', 'sage')];
@endphp
@include('partials.page-nav', ['pills' => $aboutNav])

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
          <div class="about-story__body">
            {!! \App\mh_about_story_html() !!}
          </div>
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
            <p class="about-aside-kicker">{!! \App\mh_svg_icon('briefcase', 14) !!} {{ __('Work', 'sage') }}</p>
            <h3 class="about-aside-card__title">{{ __('Studio concepts', 'sage') }}</h3>
            <p class="about-aside-card__bio">{{ __('WordPress themes and plugins I built as samples. Hire me to adapt one.', 'sage') }}</p>
            <a class="about-aside-card__link" href="{{ esc_url(\App\mh_work_listing_url()) }}">
              {{ __('See the work', 'sage') }} →
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
            {{ \App\field('about_services_intro', \App\mh_adjacent_range_copy()) }}
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

{{-- Process, fit, FAQ, glance (moved from Home) --}}
@include('partials.about-hire-sections')

{{-- HOW I WORK --}}
<section class="pf-section pf-section--alt about-approach-sec" id="approach" aria-labelledby="about-approach-heading">
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
        <p class="about-services-note">
          <a class="h-text-arrow" href="{{ home_url('/code/') }}">{{ __('Repos and stack notes on Code', 'sage') }} →</a>
        </p>
      </div>
    </div>
  </div>
</section>

{{-- ELSEWHERE --}}
<section class="pf-section about-elsewhere-sec" id="elsewhere" aria-labelledby="about-elsewhere-heading">
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
        {{ \App\field('about_cta_h2', __('Need a WordPress or full-stack developer?', 'sage')) }}
      </h2>
      <p>{{ \App\field('about_cta_lede', __('Got a question about a post, a project, or a role? Send it over. I usually reply within one business day (ET).', 'sage')) }}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ \App\field('about_cta_btn', __('Say hello', 'sage')) }}
      </a>
      <p class="cta-band__note">{{ \App\mh_reply_sla('note') }}</p>
    </div>
  </div>
</section>

@endsection
