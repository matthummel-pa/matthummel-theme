@php
  $mhSoc    = \App\mh_social_links();
  $socIcons = get_theme_mod('mh_social_style', 'icons') === 'icons';
@endphp

<header class="site-header" id="site-header">
  <div class="site-header-inner">

    {{-- Brand --}}
    <a class="brand" href="{{ home_url('/') }}" rel="home" aria-label="{{ $siteName }} — home">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
          <rect width="120" height="120" rx="22"/>
          <text x="60" y="82" text-anchor="middle">MH</text>
        </svg>
      </span>
      <span class="brand-name">{{ $siteName }}</span>
    </a>

    {{-- Primary navigation --}}
    @if (has_nav_menu('primary_navigation'))
      <nav class="header-nav" aria-label="Primary navigation">
        {!! wp_nav_menu([
          'theme_location' => 'primary_navigation',
          'menu_class'     => 'header-nav-list',
          'echo'           => false,
          'container'      => false,
          'depth'          => 1,
        ]) !!}
      </nav>
    @endif

    {{-- Right cluster --}}
    <div class="header-actions">
      @if ($mhSoc)
        <ul class="header-social" aria-label="Social links">
          @foreach ($mhSoc as $s)
            <li>
              <a href="{{ esc_url($s['url']) }}" aria-label="{{ $s['label'] }}" rel="me noopener" target="_blank">
                {!! \App\mh_social_icon($s['key']) !!}
              </a>
            </li>
          @endforeach
        </ul>
      @endif

      @if (get_theme_mod('mh_dark_enable', true))
        <button class="mh-theme-toggle" type="button" aria-label="{{ __('Toggle dark mode', 'matthummel') }}" aria-pressed="false">
          {!! \App\mh_icon('heroicon-o-moon', 'mh-icon-dark') !!}
          {!! \App\mh_icon('heroicon-o-sun', 'mh-icon-light') !!}
        </button>
      @endif

      <a class="btn btn-hire" href="{{ esc_url(home_url('/contact')) }}">
        {{ __('Hire Me', 'matthummel') }}
      </a>

      <button class="menu-toggle" aria-expanded="false" aria-controls="mh-popout" aria-label="{{ __('Open menu', 'matthummel') }}">
        <span class="bars" aria-hidden="true"></span>
      </button>
    </div>
  </div>
</header>

<div class="mh-popout-overlay" tabindex="-1"></div>
<aside id="mh-popout" class="mh-popout" aria-label="{{ __('Menu', 'matthummel') }}">
  <button class="mh-popout-close" aria-label="{{ __('Close menu', 'matthummel') }}">&times;</button>

  @if (has_nav_menu('primary_navigation'))
    <nav aria-label="{{ __('Popout menu', 'matthummel') }}">
      {!! wp_nav_menu([
        'theme_location' => 'primary_navigation',
        'menu_class'     => 'mh-popout-menu',
        'echo'           => false,
        'container'      => false,
      ]) !!}
    </nav>
  @endif

  @if ($mhSoc)
    <div class="mh-popout-socials">
      @foreach ($mhSoc as $s)
        <a href="{{ esc_url($s['url']) }}" aria-label="{{ $s['label'] }}" rel="me noopener" target="_blank">
          {!! \App\mh_social_icon($s['key']) !!}
        </a>
      @endforeach
    </div>
  @endif
</aside>
