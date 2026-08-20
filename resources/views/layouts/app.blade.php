<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
      (function () {
        try {
          var t = localStorage.getItem('mh-theme');
          if (t !== 'dark' && t !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            t = 'dark';
          }
          if (t === 'dark') document.documentElement.classList.add('mh-dark');
        } catch (e) {}
      })();
    </script>
    @php(do_action('get_header'))
    @php(wp_head())
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php(do_action('mh_head_end'))
  </head>
  <body @php(body_class())>
    @php(wp_body_open())
    <div id="app">
      <a class="skip-link" href="#main">{{ __('Skip to main content', 'sage') }}</a>
      @include('sections.header')
      <main id="main" class="main" tabindex="-1">
        @yield('content')
      </main>
      @include('sections.footer')
    </div>
    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>
