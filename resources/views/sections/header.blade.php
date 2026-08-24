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
      <a class="header-avail-dot" href="{{ home_url('/now/') }}" title="Open for work — see what I'm up to now" aria-label="Currently open for work">
        <span class="h-badge__dot" aria-hidden="true"></span>
        <span class="header-avail-label">Open for work</span>
      </a>

      <a class="btn btn-hire" href="{{ esc_url(home_url('/contact/')) }}">Say hello</a>

      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mh-popout" aria-label="{{ __('Open menu', 'matthummel') }}">
        <span class="bars" aria-hidden="true"></span>
      </button>
    </div>
  </div>
</header>

<div class="mh-popout-overlay" tabindex="-1"></div>
<aside id="mh-popout" class="mh-popout" aria-label="{{ __('Menu', 'matthummel') }}">
  <div class="mh-popout-head">
    <p class="mh-popout-kicker">Menu</p>
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
    <p class="mh-popout-kicker mh-popout-kicker--quiet">Elsewhere</p>
    @include('partials.social', ['compact' => true])
  </div>

  <a class="btn mh-popout-cta" href="{{ esc_url(home_url('/contact/')) }}">Say hello</a>
</aside>
