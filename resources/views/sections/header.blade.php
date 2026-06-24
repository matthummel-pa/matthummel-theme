<header class="banner">
  <a class="brand" href="{{ home_url('/') }}">
    {{ $siteName }}
    @if (get_bloginfo('description'))
      <small>{{ get_bloginfo('description') }}</small>
    @endif
  </a>

  @if (has_nav_menu('primary_navigation'))
    <nav class="nav-primary" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
      {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav', 'echo' => false, 'container' => false]) !!}
    </nav>
  @endif

  @php
    $socials = apply_filters('matthummel/socials', [
      'LinkedIn' => 'https://www.linkedin.com/in/matthummel',
      'Dev.to'   => 'https://dev.to/mattbuildsapps',
      'GitHub'   => 'https://github.com/matthummel-pa',
    ]);
  @endphp
  @if ($socials)
    <ul class="social" aria-label="{{ __('Social links', 'matthummel') }}">
      @foreach ($socials as $label => $url)
        <li><a href="{{ esc_url($url) }}" rel="me noopener">{{ $label }}</a></li>
      @endforeach
    </ul>
  @endif

  @if (apply_filters('matthummel/show_header_cta', true))
    <a class="btn header-cta" href="{{ esc_url(apply_filters('matthummel/header_cta_url', 'https://dev.to/mattbuildsapps')) }}">
      {{ apply_filters('matthummel/header_cta_label', __('Find me on Dev.to', 'matthummel')) }}
    </a>
  @endif
</header>
