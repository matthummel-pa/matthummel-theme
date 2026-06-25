@php
  $tb        = \App\mh_topbar();
  $mhSoc     = \App\mh_social_links();
  $socStyle  = get_theme_mod('mh_social_style', 'icons');
  $socIcons  = $socStyle === 'icons';
  $navSocial = (bool) get_theme_mod('mh_nav_social', true);
@endphp

@if ($tb['enable'])
  <div class="top-bar" style="background:{{ $tb['bg'] }};color:{{ $tb['text'] }};">
    <div class="top-bar-inner">
      @if ($tb['contact'])
        <div class="top-bar-contact">{!! wp_kses_post($tb['contact']) !!}</div>
      @endif
      <div class="top-bar-right">
        @if ($tb['show_social'] && $mhSoc)
          <ul class="top-bar-social{{ $socIcons ? ' is-icons' : '' }}" aria-label="{{ __('Social links', 'matthummel') }}">
            @foreach ($mhSoc as $s)
              <li>
                <a href="{{ esc_url($s['url']) }}" aria-label="{{ $s['label'] }}" rel="me noopener">
                  @if ($socIcons){!! \App\mh_social_icon($s['key']) !!}@else{{ $s['label'] }}@endif
                </a>
              </li>
            @endforeach
          </ul>
        @endif
        @if ($tb['cta_text'] && $tb['cta_url'])
          <a class="top-bar-cta" href="{{ esc_url($tb['cta_url']) }}">{{ $tb['cta_text'] }}</a>
        @endif
      </div>
    </div>
  </div>
@endif

<header class="banner">
  <a class="brand" href="{{ home_url('/') }}" rel="home">
    <span class="brand-mark" aria-hidden="true">
      <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="MH">
        <rect width="120" height="120" rx="30"/>
        <text x="60" y="80" text-anchor="middle">MH</text>
      </svg>
    </span>
    <span class="brand-text">
      <span class="brand-name">{{ $siteName }}</span>
      @if (get_bloginfo('description'))
        <small>{{ get_bloginfo('description') }}</small>
      @endif
    </span>
  </a>

  @if (has_nav_menu('primary_navigation'))
    <nav class="nav-primary" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
      {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav', 'echo' => false, 'container' => false]) !!}
    </nav>
  @endif

  @if ($navSocial && $mhSoc)
    <ul class="social{{ $socIcons ? ' is-icons' : '' }}" aria-label="{{ __('Social links', 'matthummel') }}">
      @foreach ($mhSoc as $s)
        <li>
          <a href="{{ esc_url($s['url']) }}" aria-label="{{ $s['label'] }}" rel="me noopener">
            @if ($socIcons){!! \App\mh_social_icon($s['key']) !!}@else{{ $s['label'] }}@endif
          </a>
        </li>
      @endforeach
    </ul>
  @endif

  @if (apply_filters('matthummel/show_header_cta', true))
    <a class="btn header-cta" href="{{ esc_url(apply_filters('matthummel/header_cta_url', 'https://dev.to/mattbuildsapps')) }}">
      {{ apply_filters('matthummel/header_cta_label', __('Find me on Dev.to', 'matthummel')) }}
    </a>
  @endif

  @if (get_theme_mod('mh_dark_enable', true))
    <button class="mh-theme-toggle" type="button" aria-label="{{ __('Toggle dark mode', 'matthummel') }}" aria-pressed="false">
      {!! \App\mh_icon('heroicon-o-moon', 'mh-icon-dark') !!}
      {!! \App\mh_icon('heroicon-o-sun', 'mh-icon-light') !!}
    </button>
  @endif

  <button class="menu-toggle" aria-expanded="false" aria-controls="mh-popout" aria-label="{{ __('Open menu', 'matthummel') }}">
    <span class="bars" aria-hidden="true"></span>
    <span class="menu-toggle-label">{{ __('Menu', 'matthummel') }}</span>
  </button>
</header>

<div class="mh-popout-overlay" tabindex="-1"></div>
<aside id="mh-popout" class="mh-popout" aria-label="{{ __('Menu', 'matthummel') }}">
  <button class="mh-popout-close" aria-label="{{ __('Close menu', 'matthummel') }}">&times;</button>

  @if (has_nav_menu('primary_navigation'))
    <nav class="mh-popout-nav" aria-label="{{ __('Popout menu', 'matthummel') }}">
      {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'mh-popout-menu', 'echo' => false, 'container' => false]) !!}
    </nav>
  @endif

  @php
    $popCols = max(1, min(4, (int) get_theme_mod('mh_popout_block_cols', 1)));
    $hasPopBlocks = false;
    for ($i = 1; $i <= $popCols; $i++) {
        if (is_active_sidebar("popout-{$i}")) { $hasPopBlocks = true; break; }
    }
  @endphp
  @if ($hasPopBlocks)
    <div class="mh-popout-blocks mh-popout-blocks--cols-{{ $popCols }}">
      @for ($i = 1; $i <= $popCols; $i++)
        <div class="mh-popout-col">
          @if (is_active_sidebar("popout-{$i}"))
            @php dynamic_sidebar("popout-{$i}"); @endphp
          @endif
        </div>
      @endfor
    </div>
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
