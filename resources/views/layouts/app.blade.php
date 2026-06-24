<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php(do_action('get_header'))
    @php(wp_head())

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php(do_action('mh_head_end'))
  </head>

  <body @php(body_class())>
    @php(wp_body_open())

    <div id="app">
      <a class="sr-only focus:not-sr-only" href="#main">
        {{ __('Skip to content', 'sage') }}
      </a>

      @include('sections.header')

      @php($mhLayout = \App\mh_active_layout())
      <div class="main-wrap @if ($mhLayout['sidebar']) main-wrap--sidebar @endif">
        <main id="main" class="main">
          @yield('content')
        </main>

        @if ($mhLayout['sidebar'])
          <aside class="mh-sidebar-area" aria-label="{{ __('Sidebar', 'matthummel') }}">
            @if (is_active_sidebar('sidebar-primary'))
              @php(dynamic_sidebar('sidebar-primary'))
            @else
              <p class="mh-sidebar-empty">{{ __('Add widgets in Appearance → Widgets (Primary Sidebar).', 'matthummel') }}</p>
            @endif
          </aside>
        @endif
      </div>

      @hasSection('sidebar')
        <aside class="sidebar">
          @yield('sidebar')
        </aside>
      @endif

      @include('sections.footer')
    </div>

    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>
