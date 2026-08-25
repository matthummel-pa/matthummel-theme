@php
  $currentPageTitle = is_front_page() ? '' : get_the_title();
@endphp
<header class="site-header" id="site-header" role="banner">
  <div class="site-header-inner">

    {{-- Brand / home link --}}
    <a class="brand" href="{{ home_url('/') }}" rel="home" aria-label="{{ __('Matt Hummel — home', 'sage') }}">
      <span class="brand-name" aria-hidden="true">Matt Hummel</span>
    </a>

    {{-- Primary navigation (desktop) --}}
    @if (has_nav_menu('primary_navigation'))
      <nav class="header-nav" aria-label="{{ __('Primary navigation', 'sage') }}">
        {!! wp_nav_menu([
          'theme_location' => 'primary_navigation',
          'menu_class'     => 'header-nav-list',
          'echo'           => false,
          'container'      => false,
          'depth'          => 1,
        ]) !!}
      </nav>
    @endif

    {{-- Header action buttons --}}
    <div class="header-actions">

      {{-- Availability signal --}}
      <a class="header-avail" href="{{ home_url('/now/') }}" aria-label="{{ __('Open for new work — see what I\'m doing now', 'sage') }}">
        <span class="header-avail__dot" aria-hidden="true"></span>
        <span class="header-avail__label">Open for work</span>
      </a>

      {{-- Desktop CTA --}}
      <a class="btn btn-hire" href="{{ esc_url(home_url('/contact/')) }}">Say hello</a>

      {{-- Mobile menu toggle --}}
      <button
        class="menu-toggle"
        type="button"
        aria-expanded="false"
        aria-controls="mh-popout"
        aria-label="{{ __('Open navigation menu', 'sage') }}"
      >
        <span class="menu-toggle__icon" aria-hidden="true">
          <span class="menu-toggle__bar"></span>
          <span class="menu-toggle__bar"></span>
          <span class="menu-toggle__bar"></span>
        </span>
      </button>

    </div>
  </div>
</header>

{{-- Mobile menu overlay --}}
<div class="mh-popout-overlay" tabindex="-1" aria-hidden="true"></div>

{{-- Mobile menu panel --}}
<div
  id="mh-popout"
  class="mh-popout"
  role="dialog"
  aria-modal="true"
  aria-label="{{ __('Navigation menu', 'sage') }}"
  aria-hidden="true"
  inert
>
  <div class="mh-popout-head">
    <div class="mh-popout-head__left">
      <p class="mh-popout-kicker">{{ __('Navigation', 'sage') }}</p>
      @if ($currentPageTitle !== '')
        <p class="mh-popout-current" aria-label="{{ __('Currently viewing:', 'sage') }} {{ $currentPageTitle }}">
          {{ $currentPageTitle }}
        </p>
      @endif
    </div>
    <button
      class="mh-popout-close"
      type="button"
      aria-label="{{ __('Close navigation menu', 'sage') }}"
    >
      <span aria-hidden="true">✕</span>
    </button>
  </div>

  @if (has_nav_menu('primary_navigation'))
    <nav class="mh-popout-nav" aria-label="{{ __('Mobile navigation', 'sage') }}">
      {!! wp_nav_menu([
        'theme_location' => 'primary_navigation',
        'menu_class'     => 'mh-popout-menu',
        'echo'           => false,
        'container'      => false,
      ]) !!}
    </nav>
  @endif

  <div class="mh-popout-elsewhere">
    <p class="mh-popout-kicker mh-popout-kicker--quiet">{{ __('Elsewhere', 'sage') }}</p>
    @include('partials.social', ['compact' => true])
  </div>

  <a class="btn mh-popout-cta" href="{{ esc_url(home_url('/contact/')) }}">
    {!! \App\mh_svg_icon('mail', 15) !!} Say hello
  </a>
</div>
