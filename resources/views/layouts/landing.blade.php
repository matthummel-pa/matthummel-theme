<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#15110d">
    {{-- Colour theme before first paint: the saved choice, else the system setting. Plain inline script so a cache plugin cannot delay it. --}}
    @php(wp_print_inline_script_tag("(function(){try{var t=localStorage.getItem('cc-theme');if(t!=='light'&&t!=='dark')t=window.matchMedia('(prefers-color-scheme: light)').matches?'light':'dark';document.documentElement.setAttribute('data-cc-theme',t)}catch(e){}})();", ['data-no-optimize' => '1', 'data-no-defer' => '1', 'data-cfasync' => 'false']))
    @php(do_action('get_header'))
    @php(wp_head())
    @vite(['resources/css/cc-landing.css', 'resources/js/cc-landing.js'])
    <link rel="icon" type="image/svg+xml" href="{{ esc_url(\App\mh_theme_image_uri('brand/cobble-candle/cobble-candle-icon.svg')) }}">
    @php(do_action('mh_head_end'))
  </head>
  <body @php(body_class('cc-landing'))>
    @php(wp_body_open())
    <a class="cc-skip" href="#main">{{ __('Skip to main content', 'sage') }}</a>
    @yield('content')
    @include('partials.consent-banner')
    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>
