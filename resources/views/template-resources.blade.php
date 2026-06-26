{{--
  Template Name: Resources
--}}

@extends('layouts.app')

@section('content')

{{-- ── PAGE HEADER ─────────────────────────────────────────────────── --}}
<header class="page-header container" data-anim="fade-up">
  <h1 class="display-title is-hero">{{ get_the_title() }}</h1>
  @if (get_the_excerpt())
    <p class="lead">{!! wp_kses_post(get_the_excerpt()) !!}</p>
  @else
    <p class="lead">{{ __('A curated list of resources for web development, marketing, and the Microsoft Power Platform — tools, docs, and tutorials I actually use.', 'matthummel') }}</p>
  @endif
</header>

<hr class="rule">

{{-- ── RESOURCE GROUPS ────────────────────────────────────────────── --}}
@php
  $groups = [
    [
      'title' => __('Web Development', 'matthummel'),
      'icon'  => '🌐',
      'items' => [
        ['label' => 'MDN Web Docs',           'url' => 'https://developer.mozilla.org/'],
        ['label' => 'CSS-Tricks',             'url' => 'https://css-tricks.com/'],
        ['label' => 'web.dev (Google)',        'url' => 'https://web.dev/'],
        ['label' => 'Smashing Magazine',      'url' => 'https://www.smashingmagazine.com/'],
        ['label' => 'Dave Rupert',            'url' => 'https://daverupert.com/'],
      ],
    ],
    [
      'title' => __('WordPress', 'matthummel'),
      'icon'  => '🔷',
      'items' => [
        ['label' => 'WordPress Developer Docs', 'url' => 'https://developer.wordpress.org/'],
        ['label' => 'Roots / Sage 10',          'url' => 'https://roots.io/sage/'],
        ['label' => 'WP Tavern',                'url' => 'https://wptavern.com/'],
        ['label' => 'WP Beginner',              'url' => 'https://www.wpbeginner.com/'],
        ['label' => 'Advanced Custom Fields',   'url' => 'https://www.advancedcustomfields.com/resources/'],
      ],
    ],
    [
      'title' => __('Microsoft Power Platform', 'matthummel'),
      'icon'  => '⚡',
      'items' => [
        ['label' => 'Microsoft Power Platform Docs', 'url' => 'https://learn.microsoft.com/en-us/power-platform/'],
        ['label' => 'Power Apps Docs',               'url' => 'https://learn.microsoft.com/en-us/power-apps/'],
        ['label' => 'Power Automate Docs',           'url' => 'https://learn.microsoft.com/en-us/power-automate/'],
        ['label' => 'Power Platform Community',      'url' => 'https://powerusers.microsoft.com/'],
        ['label' => 'SharePoint Look Book',          'url' => 'https://lookbook.microsoft.com/'],
      ],
    ],
    [
      'title' => __('Learning & Growth', 'matthummel'),
      'icon'  => '📚',
      'items' => [
        ['label' => 'freeCodeCamp',     'url' => 'https://www.freecodecamp.org/'],
        ['label' => 'The Odin Project', 'url' => 'https://www.theodinproject.com/'],
        ['label' => 'Kevin Powell (CSS)', 'url' => 'https://www.youtube.com/@KevinPowell'],
        ['label' => 'Fireship',         'url' => 'https://www.youtube.com/@Fireship'],
        ['label' => 'Scrimba',          'url' => 'https://scrimba.com/'],
      ],
    ],
    [
      'title' => __('SEO & Marketing', 'matthummel'),
      'icon'  => '📈',
      'items' => [
        ['label' => 'Google Search Central',   'url' => 'https://developers.google.com/search'],
        ['label' => 'Ahrefs Blog',             'url' => 'https://ahrefs.com/blog/'],
        ['label' => 'Backlinko',               'url' => 'https://backlinko.com/'],
        ['label' => 'Search Engine Journal',   'url' => 'https://www.searchenginejournal.com/'],
      ],
    ],
    [
      'title' => __('Tools I Use', 'matthummel'),
      'icon'  => '🛠',
      'items' => [
        ['label' => 'VS Code',       'url' => 'https://code.visualstudio.com/'],
        ['label' => 'GitHub',        'url' => 'https://github.com/'],
        ['label' => 'Vite',          'url' => 'https://vitejs.dev/'],
        ['label' => 'Supabase',      'url' => 'https://supabase.com/'],
        ['label' => 'Figma',         'url' => 'https://www.figma.com/'],
        ['label' => 'TablePlus',     'url' => 'https://tableplus.com/'],
      ],
    ],
  ];
@endphp

<section class="resources-section container">
  <div class="resources-grid">
    @foreach ($groups as $group)
      <div class="resource-group" data-anim="fade-up">
        <h2 class="resource-group-title">{{ $group['icon'] }} {{ $group['title'] }}</h2>
        <ul class="resource-list">
          @foreach ($group['items'] as $item)
            <li>
              <a href="{{ esc_url($item['url']) }}" target="_blank" rel="noopener noreferrer">
                {{ $item['label'] }}
                <span aria-hidden="true">↗</span>
              </a>
            </li>
          @endforeach
        </ul>
      </div>
    @endforeach
  </div>
</section>

{{-- Show any page content (for extra copy added via editor) --}}
@if (get_the_content())
  <hr class="rule">
  <section class="container post-prose">
    @php(the_content())
  </section>
@endif

@include('partials.cta')

@endsection
