{{--
  Journal (the posts page) and the last-resort template for any other list.
  The Journal's copy and its post list are blocks saved on the posts page; this file prints them.
  The old field version is in resources/archive/views/index.blade.php.
--}}
@extends('layouts.app')

@section('content')
@php
  $writeId = \App\mh_writing_id();
  $writeUrl = $writeId ? (string) get_permalink($writeId) : home_url('/blog/');
  $usesBlocks = is_home() && $writeId > 0 && \App\mh_page_uses_blocks($writeId);
  $journalHtml = '';
  $blogName = $writeId ? get_the_title($writeId) : __('Journal', 'sage');
  $blogDescription = '';

  if ($usesBlocks) {
    $journalPage = get_post($writeId);

    // Blog schema: name and description are the hero's heading and intro.
    foreach (parse_blocks((string) $journalPage->post_content) as $parsed) {
      if (($parsed['blockName'] ?? '') !== 'matthummel/hero') {
        continue;
      }
      foreach ($parsed['innerBlocks'] as $inner) {
        $plain = trim(wp_strip_all_tags((string) ($inner['innerHTML'] ?? '')));
        if ($plain === '') {
          continue;
        }
        if (($inner['blockName'] ?? '') === 'core/heading' && (int) ($inner['attrs']['level'] ?? 2) === 1) {
          $blogName = $plain;
        } elseif (($inner['blockName'] ?? '') === 'core/paragraph' && $blogDescription === '') {
          $blogDescription = $plain;
        }
      }
      break;
    }

    // WordPress does not run the posts page's own content, so run it here with the page as the current post.
    $GLOBALS['post'] = $journalPage;
    setup_postdata($journalPage);
    $journalHtml = apply_filters('the_content', (string) $journalPage->post_content);
    wp_reset_postdata();
  }

  $blogLd = array_filter([
    '@context'    => 'https://schema.org',
    '@type'       => 'Blog',
    'name'        => $blogName,
    'description' => $blogDescription,
    'url'         => esc_url_raw($writeUrl),
    'inLanguage'  => 'en-US',
    'author'      => [
      '@type' => 'Person',
      'name'  => 'Matt Hummel',
      'url'   => home_url('/'),
    ],
    'publisher' => [
      '@type' => 'Person',
      'name'  => 'Matt Hummel',
      'url'   => home_url('/'),
    ],
    'potentialAction' => [
      '@type'       => 'SearchAction',
      'target'      => [
        '@type'       => 'EntryPoint',
        'urlTemplate' => home_url('/?s={search_term_string}'),
      ],
      'query-input' => 'required name=search_term_string',
    ],
  ]);
  $blogLdJson = wp_json_encode($blogLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp

@if ($usesBlocks)
  {{-- JSON-LD: Blog schema for Google / AI search --}}
  <script type="application/ld+json">{!! $blogLdJson !!}</script>
  <div class="mh-block-page mh-block-page--journal">
    {!! $journalHtml !!}
  </div>
@else
  {{-- The posts page has no blocks yet, or this is another kind of list: a title and the posts. --}}
  @component('partials.page-hero')
    <h1 class="display-title is-hero">{{ is_home() ? $blogName : wp_strip_all_tags(get_the_archive_title()) }}</h1>
  @endcomponent
  <div class="container wide page-block write-hub" data-journal data-journal-home="{{ esc_url($writeUrl) }}" data-journal-list="post-list post-list--roomy">
    @include('partials.write-toolbar', compact('writeId', 'writeUrl'))
    <div data-journal-region="topics">
      @include('partials.write-topics', compact('writeId', 'writeUrl'))
    </div>
    <div id="journal-posts" data-journal-region="results">
      @include('partials.journal-results', ['writeUrl' => $writeUrl])
    </div>
  </div>
@endif
@endsection
