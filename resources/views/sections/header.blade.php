<header class="site-header" id="site-header">
  <div class="site-header-inner">
    <a class="brand" href="{{ home_url('/') }}" rel="home">
      <span class="brand-name">Matt Hummel</span>
    </a>

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

    <div class="header-actions">
      <button class="mh-theme-toggle" type="button" aria-pressed="false" aria-label="{{ __('Switch to dark mode', 'sage') }}">
        <span class="mh-icon-dark">{!! \App\mh_svg_icon('moon', 20) !!}</span>
        <span class="mh-icon-light">{!! \App\mh_svg_icon('sun', 20) !!}</span>
      </button>

      <a class="btn btn-hire" href="{{ esc_url(home_url('/contact/')) }}">{{ __('Say hello', 'matthummel') }}</a>

      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mh-popout" aria-label="{{ __('Open menu', 'matthummel') }}">
        <span class="bars" aria-hidden="true"></span>
      </button>
    </div>
  </div>
</header>

<div class="mh-popout-overlay" tabindex="-1"></div>
<aside id="mh-popout" class="mh-popout" aria-label="{{ __('Menu', 'matthummel') }}">
  <div class="mh-popout-head">
    <p class="mh-popout-kicker">{{ __('Menu', 'sage') }}</p>
    <button class="mh-popout-close" type="button" aria-label="{{ __('Close menu', 'matthummel') }}">&times;</button>
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

  <a class="btn mh-popout-cta" href="{{ esc_url(home_url('/contact/')) }}">{{ __('Say hello', 'matthummel') }}</a>
</aside>
