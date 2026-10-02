{{--
  Template Name: Code
--}}
@extends('layouts.app')

@section('content')
@php
  // One stored GitHub snapshot (refreshed hourly by WP-Cron); see app/code-github.php.
  $gh = \App\mh_code_gh_snapshot();
  $practiceGroups = \App\mh_code_page_practice_grouped();
  $skillGroups = \App\mh_code_page_skills_grouped();
  $docGroups = \App\mh_code_page_resources_grouped();
  $login = $gh['login'];
  $ghUrl = $gh['url'];
@endphp

@include('partials.code-gh-top', ['gh' => $gh])

{{-- SKILLS --}}
<section class="pf-section code-skills-sec" id="skills" aria-labelledby="code-skills-heading">
  <div class="container wide">
    <div class="code-skills-shell">
      <div class="code-skills-shell__mesh" aria-hidden="true"></div>
      <div class="code-skills-shell__inner">
        <header class="code-skills-shell__head">
          <p class="eyebrow">{{ __('Stack', 'sage') }}</p>
          <h2 id="code-skills-heading" class="display-title is-section">
            {{ \App\field('code_sk_h2', __('Skills and tools.', 'sage')) }}
          </h2>
          <p class="sec-intro">
            {{ \App\field('code_sk_intro', __('WordPress, Sage, Tailwind, and the rest of the stack behind shipped repos. Jump a shelf — not an exhaustive list, just what shows up in public GitHub.', 'sage')) }}
          </p>
          @if (count($skillGroups) > 1)
            <nav class="code-skills-jump" aria-label="{{ __('Skill groups', 'sage') }}">
              @foreach ($skillGroups as $group)
                <a href="#skill-{{ sanitize_title($group['label']) }}">
                  <span class="code-skills-jump__ico" aria-hidden="true">{!! \App\mh_svg_icon($group['icon'], 13) !!}</span>
                  {{ $group['label'] }}
                  <span class="code-skills-jump__n">{{ number_format_i18n(count($group['items'])) }}</span>
                </a>
              @endforeach
            </nav>
          @endif
        </header>

        @if ($gh['languages'])
          <div class="code-langs" aria-labelledby="code-langs-heading">
            <div class="code-langs__head">
              <h3 class="code-langs__title" id="code-langs-heading">{!! \App\mh_svg_icon('chart-bar', 16) !!} {{ \App\field('code_lang_h3', __('Languages on GitHub', 'sage')) }}</h3>
              <p class="code-langs__intro">{{ \App\field('code_lang_intro', __('Primary language of each public repo, counted live. Small static sites pull HTML up; the themes and plugins are PHP.', 'sage')) }}</p>
            </div>
            <div class="code-langs__bar" aria-hidden="true">
              @foreach ($gh['languages'] as $l)
                <span style="--w: {{ $l['pct'] }}%; --c: {{ esc_attr($l['color']) }}"></span>
              @endforeach
            </div>
            <ul class="code-langs__legend">
              @foreach ($gh['languages'] as $l)
                <li>
                  <span class="repo-lang__dot" style="--lang-color: {{ esc_attr($l['color']) }}" aria-hidden="true"></span>
                  <span class="code-langs__name">{{ $l['lang'] }}</span>
                  <span class="code-langs__pct">{{ number_format_i18n($l['pct'], 0) }}%</span>
                  <span class="code-langs__n">{{ sprintf(_n('%s repo', '%s repos', $l['count'], 'sage'), number_format_i18n($l['count'])) }}</span>
                </li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="code-skills-groups">
          @foreach ($skillGroups as $group)
            <section
              class="code-skills-group"
              id="skill-{{ sanitize_title($group['label']) }}"
              aria-labelledby="skill-head-{{ sanitize_title($group['label']) }}"
              data-group="{{ esc_attr($group['label']) }}"
            >
              <div class="code-skills-group__head">
                <span class="code-skills-group__mark" aria-hidden="true">{!! \App\mh_svg_icon($group['icon'], 16) !!}</span>
                <h3 class="code-skills-group__title" id="skill-head-{{ sanitize_title($group['label']) }}">{{ $group['label'] }}</h3>
                <span class="code-skills-group__rule" aria-hidden="true"></span>
                <span class="code-skills-group__count">{{ number_format_i18n(count($group['items'])) }}</span>
              </div>
              <ul class="code-skills-grid">
                @foreach ($group['items'] as $skill)
                  <li class="code-skills__card" data-group="{{ esc_attr($group['label']) }}">
                    {!! \App\mh_skill_tile($skill['name'], $skill['hint']) !!}
                  </li>
                @endforeach
              </ul>
            </section>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

{{-- PRACTICE --}}
<section class="pf-section code-practice-sec" id="practice" aria-labelledby="code-practice-heading">
  <div class="container wide">
    <div class="code-practice-shell">
      <div class="code-practice-shell__mesh" aria-hidden="true"></div>
      <div class="code-practice-shell__inner code-practice-layout">
        <aside class="code-practice-aside" aria-label="{{ __('Practice overview', 'sage') }}">
          <p class="eyebrow">{{ __('Day to day', 'sage') }}</p>
          <h2 id="code-practice-heading" class="display-title is-section">
            {{ \App\field('code_do_h2', __('What I work on.', 'sage')) }}
          </h2>
          <p class="sec-intro">
            {{ \App\field('code_do_intro', __('I ship across the stack: custom WordPress themes and plugins, PHP, TypeScript, React, APIs, and data-backed applications. The public repos show how I structure code, document decisions, and prepare work for handoff.', 'sage')) }}
          </p>
          @if (count($practiceGroups) > 1)
            <nav class="code-practice-jump" aria-label="{{ __('Practice groups', 'sage') }}">
              @foreach ($practiceGroups as $group)
                <a href="#practice-{{ sanitize_title($group['label']) }}">
                  <span class="code-practice-jump__ico" aria-hidden="true">{!! \App\mh_svg_icon($group['icon'], 13) !!}</span>
                  {{ $group['label'] }}
                  <span class="code-practice-jump__n">{{ number_format_i18n(count($group['items'])) }}</span>
                </a>
              @endforeach
            </nav>
          @endif
          <div class="code-practice-shell__links">
            <a class="code-practice-shell__link" href="{{ home_url('/hire/') }}">
              {!! \App\mh_svg_icon('briefcase', 13) !!}
              {{ __('Hire me', 'sage') }}
            </a>
            <a class="code-practice-shell__link" href="{{ esc_url(\App\mh_work_listing_url()) }}">
              {!! \App\mh_svg_icon('globe', 13) !!}
              {{ __('See the work', 'sage') }}
            </a>
          </div>
        </aside>

        <div class="code-practice-board">
          @foreach ($practiceGroups as $group)
            <section
              class="code-practice-group"
              id="practice-{{ sanitize_title($group['label']) }}"
              aria-labelledby="practice-head-{{ sanitize_title($group['label']) }}"
              data-group="{{ esc_attr($group['label']) }}"
            >
              <div class="code-practice-group__head">
                <span class="code-practice-group__mark" aria-hidden="true">{!! \App\mh_svg_icon($group['icon'], 16) !!}</span>
                <h3 class="code-practice-group__title" id="practice-head-{{ sanitize_title($group['label']) }}">{{ $group['label'] }}</h3>
                <span class="code-practice-group__rule" aria-hidden="true"></span>
                <span class="code-practice-group__count">{{ sprintf(_n('%s focus', '%s focuses', count($group['items']), 'sage'), number_format_i18n(count($group['items']))) }}</span>
              </div>
              <ol class="code-practice-grid">
                @foreach ($group['items'] as $i => $item)
                  <li class="code-practice-card" data-group="{{ esc_attr($group['label']) }}">
                    <span class="code-practice-card__n" aria-hidden="true">{{ sprintf('%02d', $i + 1) }}</span>
                    <div class="code-practice-card__copy">
                      <h4 class="code-practice-card__title">{{ $item['title'] }}</h4>
                      @if ($item['body'] !== '')
                        <p class="code-practice-card__body">{{ $item['body'] }}</p>
                      @endif
                    </div>
                  </li>
                @endforeach
              </ol>
            </section>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

@include('partials.code-gh-community', ['gh' => $gh])

{{-- DOCUMENTATION --}}
<section class="pf-section code-docs-sec" id="docs" aria-labelledby="code-docs-heading">
  <div class="container wide">
    <div class="code-docs-shell">
      <div class="code-docs-shell__mesh" aria-hidden="true"></div>
      <div class="code-docs-shell__inner">
        <header class="code-docs-shell__head">
          <p class="eyebrow">{{ __('Reference', 'sage') }}</p>
          <h2 id="code-docs-heading" class="display-title is-section">
            {{ \App\field('code_doc_h2', __('Documentation I keep open.', 'sage')) }}
          </h2>
          <p class="sec-intro">
            {{ \App\field('code_doc_intro', __('Official handbooks first, then Roots and the front-end stack behind this site. Jump a shelf, open a card — every link is the official docs.', 'sage')) }}
          </p>
          @if (count($docGroups) > 1)
            <nav class="code-docs-jump" aria-label="{{ __('Documentation groups', 'sage') }}">
              @foreach ($docGroups as $group)
                <a href="#doc-{{ sanitize_title($group['label']) }}">
                  <span class="code-docs-jump__ico" aria-hidden="true">{!! \App\mh_svg_icon($group['icon'], 13) !!}</span>
                  {{ $group['label'] }}
                  <span class="code-docs-jump__n">{{ number_format_i18n(count($group['items'])) }}</span>
                </a>
              @endforeach
            </nav>
          @endif
        </header>

        <div class="code-docs-groups">
          @foreach ($docGroups as $group)
            <section class="code-docs-group" id="doc-{{ sanitize_title($group['label']) }}" data-group="{{ esc_attr($group['label']) }}" aria-labelledby="doc-heading-{{ sanitize_title($group['label']) }}">
              <div class="code-docs-group__head">
                <span class="code-docs-group__mark" aria-hidden="true">{!! \App\mh_svg_icon($group['icon'], 16) !!}</span>
                <h3 id="doc-heading-{{ sanitize_title($group['label']) }}" class="code-docs-group__title">{{ $group['label'] }}</h3>
                <span class="code-docs-group__rule" aria-hidden="true"></span>
                <span class="code-docs-group__count">{{ sprintf(_n('%s link', '%s links', count($group['items']), 'sage'), number_format_i18n(count($group['items']))) }}</span>
              </div>
              <ul class="code-docs">
                @foreach ($group['items'] as $doc)
                  <li class="code-docs__card" data-group="{{ esc_attr($doc['group']) }}">
                    <a class="code-docs__hit" href="{{ esc_url($doc['url']) }}" rel="noopener" target="_blank">
                      <span class="code-docs__mark" aria-hidden="true">{!! \App\mh_svg_icon($doc['icon'], 18) !!}</span>
                      <span class="code-docs__copy">
                        <span class="code-docs__title">{{ $doc['label'] }}</span>
                        @if (($doc['note'] ?? '') !== '')
                          <span class="code-docs__note">{{ $doc['note'] }}</span>
                        @endif
                      </span>
                      <span class="code-docs__foot">
                        @if (($doc['host'] ?? '') !== '')
                          <span class="code-docs__host">{{ $doc['host'] }}</span>
                        @endif
                        <span class="code-docs__open" aria-hidden="true">{{ __('Open', 'sage') }} →</span>
                      </span>
                      <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
                    </a>
                  </li>
                @endforeach
              </ul>
            </section>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

{{-- CTA --}}
<section class="cta-band code-cta" aria-labelledby="code-cta-heading" data-reveal>
  <div class="container wide cta-band-inner">
    <div class="cta-band__copy code-cta__copy">
      <p class="eyebrow eyebrow--on-dark">{{ \App\field('code_cta_kicker', __('Work together', 'sage')) }}</p>
      <h2 id="code-cta-heading" class="display-title is-section">
        {{ \App\field('code_cta_h2', __('Hire me, or compare notes.', 'sage')) }}
      </h2>
      <p>{{ \App\field('code_cta_lede', __('Fork a repo or copy a snippet — free to use. Write if you want to work together.', 'sage')) }}</p>
    </div>
    <div class="cta-band__actions code-cta__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ __('Say hello', 'sage') }}
      </a>
      <a class="code-cta__gh" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
        {!! \App\mh_svg_icon('github', 14) !!}
        {{ '@'.$login }} →
        <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
      </a>
      <p class="cta-band__note">{{ \App\mh_reply_sla('note') }}</p>
    </div>
  </div>
</section>

@endsection
