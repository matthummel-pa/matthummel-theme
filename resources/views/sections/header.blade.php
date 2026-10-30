@php
  $currentPageTitle = '';
  if (is_singular()) {
    $currentPageTitle = \App\mh_post_title();
  } elseif (is_home()) {
    $blog = (int) get_option('page_for_posts');
    $currentPageTitle = $blog ? \App\mh_post_title($blog) : __('Journal', 'sage');
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

      <button
        type="button"
        class="theme-toggle"
        data-theme-toggle
        aria-pressed="true"
        aria-label="{{ esc_attr__('Dark mode', 'sage') }}"
      >
        <span class="theme-toggle__icon theme-toggle__icon--sun" aria-hidden="true">{!! \App\mh_svg_icon('sun', 16) !!}</span>
        <span class="theme-toggle__icon theme-toggle__icon--moon" aria-hidden="true">{!! \App\mh_svg_icon('moon', 16) !!}</span>
      </button>

      {{-- WCAG 2.2.2: a way to stop the looping animations. Remembered in localStorage (mh-motion). --}}
      <button
        type="button"
        class="theme-toggle motion-toggle"
        data-motion-toggle
        aria-pressed="false"
        aria-label="{{ esc_attr__('Pause animations', 'sage') }}"
      >
        <span class="motion-toggle__icon motion-toggle__icon--pause" aria-hidden="true">{!! \App\mh_svg_icon('pause', 16) !!}</span>
        <span class="motion-toggle__icon motion-toggle__icon--play" aria-hidden="true">{!! \App\mh_svg_icon('play', 16) !!}</span>
      </button>

      {{-- One call to action: the Get updates pill with its preview. Say hello (→ Contact) joins it only while GitHub says not hireable. --}}
      @php
        $ghAvail = \App\Github::fetchUser(\App\mh_github_login());
        $isHireable = \App\mh_is_hireable($ghAvail);
      @endphp
      @include('partials.header-updates')


      @unless ($isHireable)
        <a class="btn btn-hire" href="{{ esc_url(home_url('/contact/')) }}">{{ __('Say hello', 'sage') }}</a>
      @endunless

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

  <a class="btn mh-popout-cta" href="{{ esc_url(home_url('/get-updates/')) }}">
    {!! \App\mh_svg_icon('mail', 15) !!} {{ __('Get updates', 'sage') }}
  </a>
</div>
