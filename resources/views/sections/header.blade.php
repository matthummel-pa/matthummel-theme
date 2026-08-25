@php
  $currentPageTitle = '';
  if (is_singular()) {
    $currentPageTitle = get_the_title();
  } elseif (is_home()) {
    $blog = (int) get_option('page_for_posts');
    $currentPageTitle = $blog ? get_the_title($blog) : __('Journal', 'sage');
  } elseif (is_search()) {
    $currentPageTitle = __('Search', 'sage');
  } elseif (is_404()) {
    $currentPageTitle = __('Not found', 'sage');
  } elseif (is_archive()) {
    $currentPageTitle = get_the_archive_title();
    $currentPageTitle = wp_strip_all_tags($currentPageTitle);
  }
@endphp
<header class="site-header" id="site-header">
  <div class="site-header-inner">

    {{-- Brand / home link --}}
    <a class="brand" href="{{ home_url('/') }}" rel="home">
      <span class="brand-name">Matt Hummel</span>
    </a>

    {{-- Primary navigation (desktop) --}}
    @if (has_nav_menu('primary_navigation'))
      <nav class="header-nav" aria-label="{{ __('Primary', 'sage') }}">
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
        aria-label="{{ __('Open menu', 'sage') }}"
        data-label-open="{{ esc_attr__('Open menu', 'sage') }}"
        data-label-close="{{ esc_attr__('Close menu', 'sage') }}"
      >
        <span class="menu-toggle__icon" aria-hidden="true">
          <span class="menu-toggle__bar"></span>
          <span class="menu-toggle__bar"></span>
          <span class="menu-toggle__bar"></span>
        </span>
        <span class="menu-toggle__text">{{ __('Menu', 'sage') }}</span>
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
  aria-labelledby="mh-popout-title"
  aria-hidden="true"
  inert
>
  <div class="mh-popout-head">
    <div class="mh-popout-head__left">
      <p class="mh-popout-kicker" id="mh-popout-title">{{ __('Menu', 'sage') }}</p>
      @if ($currentPageTitle !== '')
        <p class="mh-popout-current">
          <span class="visually-hidden">{{ __('Currently viewing:', 'sage') }} </span>{{ $currentPageTitle }}
        </p>
      @endif
    </div>
    <button
      class="mh-popout-close"
      type="button"
      aria-label="{{ __('Close menu', 'sage') }}"
    >
      <span aria-hidden="true">✕</span>
    </button>
  </div>

  @if (has_nav_menu('primary_navigation'))
    <nav class="mh-popout-nav" aria-label="{{ __('Site pages', 'sage') }}">
      {!! wp_nav_menu([
        'theme_location' => 'primary_navigation',
        'menu_class'     => 'mh-popout-menu',
        'echo'           => false,
        'container'      => false,
        'depth'          => 1,
        'items_wrap'     => sprintf(
          '<ul class="%%2$s"><li class="menu-item%s"><a href="%s"%s>%s</a></li>%%3$s</ul>',
          is_front_page() ? ' current-menu-item' : '',
          esc_url(home_url('/')),
          is_front_page() ? ' aria-current="page"' : '',
          esc_html__('Home', 'sage')
        ),
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
