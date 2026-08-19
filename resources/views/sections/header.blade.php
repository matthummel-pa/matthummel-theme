@php
  $mhSoc = \App\mh_social_links();
@endphp

<header class="site-header" id="site-header">
  <div class="site-header-inner">
    <a class="brand" href="{{ home_url('/') }}" rel="home">
      @include('partials.profile-photo', ['size' => 32, 'class' => 'profile-photo profile-photo--header', 'decorative' => true, 'eager' => true])
      <span class="brand-name">Matt Hummel</span>
    </a>

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

    <div class="header-actions">
      <button class="mh-theme-toggle" type="button" aria-label="{{ __('Toggle dark mode', 'matthummel') }}" aria-pressed="false">
        <span class="mh-icon-dark" aria-hidden="true">Dark</span>
        <span class="mh-icon-light" aria-hidden="true">Light</span>
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
  <button class="mh-popout-close" type="button" aria-label="{{ __('Close menu', 'matthummel') }}">&times;</button>
  @if (has_nav_menu('primary_navigation'))
    <nav aria-label="{{ __('Mobile', 'sage') }}">
      {!! wp_nav_menu([
        'theme_location' => 'primary_navigation',
        'menu_class'     => 'mh-popout-menu',
        'echo'           => false,
        'container'      => false,
      ]) !!}
    </nav>
  @endif
  @if ($mhSoc)
    <ul class="elsewhere">
      @foreach ($mhSoc as $s)
        <li><a href="{{ esc_url($s['url']) }}" rel="me noopener" target="_blank">{{ $s['label'] }}</a></li>
      @endforeach
    </ul>
  @endif
  <a class="btn" href="{{ esc_url(home_url('/contact/')) }}">{{ __('Say hello', 'matthummel') }}</a>
</aside>
