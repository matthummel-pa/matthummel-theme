@php
  $postId  = $postId  ?? (int) get_the_ID();
  $summary = $summary ?? '';
  $toc     = $toc     ?? [];
  $popular = \App\mh_popular_posts(5, $postId);
  $cats    = get_categories(['hide_empty' => true, 'number' => 10]);
@endphp
<aside class="post-sidebar" aria-label="Sidebar">

  {{-- AI-generated summary --}}
  @if ($summary !== '')
    <details class="side-card side-card--fold">
      <summary class="side-card-title">TL;DR</summary>
      <p class="side-summary">{{ $summary }}</p>
    </details>
  @endif

  {{-- Table of contents (desktop) --}}
  @if ($toc)
    <details class="side-card side-card--toc side-card--fold">
      <summary class="side-card-title">On this page</summary>
      <nav aria-label="Table of contents">
        <ol class="side-toc">
          @foreach ($toc as $item)
            <li class="side-toc-h{{ $item['level'] }}">
              <a href="#{{ esc_attr($item['id']) }}">{{ $item['text'] }}</a>
            </li>
          @endforeach
        </ol>
      </nav>
    </details>
  @endif

  {{-- About the author --}}
  @php
    $gh = \App\Github::fetchUser(\App\mh_github_login());
    $hireable = \App\mh_is_hireable($gh);
  @endphp
  <div class="side-card side-author">
    <p class="side-author__eyebrow">{{ __('About the author', 'sage') }}</p>
    <div class="side-author__head">
      @include('partials.profile-photo', ['size' => 56, 'class' => 'profile-photo side-author__photo', 'decorative' => true])
      <div class="side-author__intro">
        <p class="side-author__name">Matt Hummel</p>
        <p class="side-author__role">{{ __('Full-stack developer · WordPress specialist · Gettysburg, PA', 'sage') }}</p>
        @if ($hireable)
          <p class="side-author__avail">
            @include('partials.avail-mark', ['gh' => $gh])
            {{ \App\mh_availability_label($gh, __('Open for new work', 'sage')) }}
          </p>
        @endif
      </div>
    </div>
    <p class="side-author__bio">
      {{ __('I write about WordPress, PHP, and the tools I use on real projects — usually with code you can paste in.', 'sage') }}
    </p>
    <div class="side-author__actions">
      <a class="side-author__btn side-author__btn--primary" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 14) !!}
        {{ __('Say hello', 'sage') }}
      </a>
      <a class="side-author__btn" href="{{ home_url('/about/') }}">{{ __('About', 'sage') }}</a>
    </div>
  </div>

  {{-- Newsletter / RSS --}}
  <div class="side-card side-subscribe">
    <h2 class="side-card-title">Follow along</h2>
    <p class="side-subscribe__body">New posts in your RSS reader. No email list, no ads.</p>
    <a class="side-subscribe__btn" href="{{ esc_url(home_url('/feed/')) }}" rel="alternate" type="application/rss+xml">
      {!! \App\mh_svg_icon('rss', 16) !!} RSS feed
    </a>
  </div>

  {{-- Popular posts --}}
  @if ($popular)
    <section class="side-card" aria-labelledby="side-pop-h">
      <h2 id="side-pop-h" class="side-card-title">Popular</h2>
      <ul class="side-posts">
        @foreach ($popular as $p)
          <li>
            <a href="{{ esc_url($p['url']) }}">{{ $p['title'] }}</a>
            <span class="side-date">{{ $p['date'] }}</span>
          </li>
        @endforeach
      </ul>
    </section>
  @endif

  {{-- Topics --}}
  @if ($cats && ! is_wp_error($cats))
    <section class="side-card" aria-labelledby="side-cat-h">
      <h2 id="side-cat-h" class="side-card-title">Topics</h2>
      <ul class="side-cats">
        @foreach ($cats as $cat)
          <li>
            <a href="{{ esc_url(get_category_link($cat)) }}">{{ $cat->name }}</a>
            <span class="side-count">{{ (int) $cat->count }}</span>
          </li>
        @endforeach
      </ul>
    </section>
  @endif

  {{-- Hire me CTA (GitHub hireable) --}}
  @if (\App\mh_is_hireable($gh))
  <div class="side-card side-hire">
    <h2 class="side-card-title">
      @include('partials.avail-mark', ['gh' => $gh])
      {{ \App\mh_availability_label($gh, __('Open for work', 'sage')) }}
    </h2>
    <p class="side-hire__body">WordPress sites, plugins, and web apps. Full-time, contract, or freelance.</p>
    <a class="btn" href="{{ home_url('/contact/') }}" style="width:100%;justify-content:center">
      {!! \App\mh_svg_icon('mail', 15) !!} Say hello
    </a>
  </div>
  @endif

</aside>
