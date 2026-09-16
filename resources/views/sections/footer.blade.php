@php
  $gh          = \App\Github::fetchUser(\App\mh_github_login());
  $footerName  = $gh['name'] ?: 'Matt Hummel';
  $ghUrl       = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $writing     = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $footerHomeId = (int) get_option('page_on_front');
  $footerBlurb = \App\field(
    'footer_blurb',
    __('Full-stack and WordPress developer. Concept work, public GitHub, and tools I recommend — with clear affiliate disclosure when a link is compensated.', 'sage'),
    $footerHomeId > 0 ? $footerHomeId : null
  );
@endphp
<footer class="site-footer">
  <div class="container wide footer-inner">

    {{-- Brand + blurb --}}
    <div class="footer-brand-col">
      <a class="footer-brand-link" href="{{ home_url('/') }}" rel="home">
        <img
          class="brand-mark brand-mark--footer"
          src="{{ esc_url(\App\mh_logo_uri('mark')) }}"
          alt=""
          width="36"
          height="36"
          decoding="async"
        >
        <span class="brand-name footer-brand">{{ $footerName }}</span>
      </a>
      <p class="footer-blurb">{{ $footerBlurb }}@if (\App\mh_is_hireable($gh)) {{ __('Open for new work and collaboration.', 'sage') }}@endif</p>
      @if (\App\mh_is_hireable($gh))
        <p class="footer-avail">
          @include('partials.avail-mark', ['gh' => $gh])
          {{ \App\mh_availability_label($gh, __('Open for work', 'sage')) }} — full-time, contract, freelance, or agency overflow
        </p>
      @endif
      <div class="footer-quick-links">
        <a href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 14) !!} Say hello</a>
        <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">{!! \App\mh_svg_icon('github', 14) !!} GitHub</a>
        <a href="{{ home_url('/feed/') }}" rel="alternate" type="application/rss+xml">{!! \App\mh_svg_icon('rss', 14) !!} RSS</a>
      </div>
    </div>

    {{-- Work --}}
    <nav class="footer-nav-col" aria-label="Work">
      <p class="footer-nav-label">{{ __('Work', 'sage') }}</p>
      <ul class="footer-nav">
        <li><a href="{{ esc_url(\App\mh_work_listing_url()) }}">{{ __('Projects', 'sage') }}</a></li>
        <li><a href="{{ home_url('/hire/') }}">{{ __('Hire me', 'sage') }}</a></li>
        <li><a href="{{ home_url('/#services') }}">{{ __('Services', 'sage') }}</a></li>
        <li><a href="{{ home_url('/code/') }}">{{ __('Code', 'sage') }}</a></li>
      </ul>
    </nav>

    {{-- Site --}}
    <nav class="footer-nav-col" aria-label="Site">
      <p class="footer-nav-label">Site</p>
      <ul class="footer-nav">
        <li><a href="{{ home_url('/about/') }}">{{ __('About', 'sage') }}</a></li>
        <li><a href="{{ $writing }}">{{ __('Journal', 'sage') }}</a></li>
        <li><a href="{{ home_url('/now/') }}">{{ __('Now', 'sage') }}</a></li>
        <li><a href="{{ home_url('/contact/') }}">{{ __('Contact', 'sage') }}</a></li>
      </ul>
    </nav>

    {{-- Elsewhere --}}
    <div class="footer-nav-col">
      <p class="footer-nav-label">Elsewhere</p>
      @include('partials.social')
    </div>

  </div>

  <div class="footer-bottom container wide">
    <p class="footer-copy">&copy; {{ date('Y') }} {{ $footerName }}.</p>
    @php
      $bottomMenuItems = has_nav_menu('footer_bottom_navigation')
        ? wp_get_nav_menu_items(get_nav_menu_locations()['footer_bottom_navigation'] ?? 0) ?: []
        : [];
    @endphp
    @if ($bottomMenuItems)
      <nav class="footer-legal-links" aria-label="Legal">
        @foreach ($bottomMenuItems as $i => $item)
          @if ($i > 0)<span aria-hidden="true">·</span>@endif
          <a href="{{ esc_url($item->url) }}"{{ $item->target ? ' target="'.esc_attr($item->target).'" rel="noopener"' : '' }}>{{ esc_html($item->title) }}</a>
        @endforeach
      </nav>
    @else
      <nav class="footer-legal-links" aria-label="Legal">
        <a href="{{ home_url('/privacy-policy/') }}">Privacy</a>
        <span aria-hidden="true">·</span>
        <a href="{{ home_url('/terms-of-use/') }}">Terms</a>
        <span aria-hidden="true">·</span>
        <a href="{{ home_url('/affiliate-disclosure/') }}">{{ __('Affiliate disclosure', 'sage') }}</a>
        <span aria-hidden="true">·</span>
        <a href="{{ home_url('/accessibility/') }}">Accessibility</a>
      </nav>
    @endif
    <p class="footer-stack">Built with <a href="https://roots.io/sage/" rel="noopener" target="_blank">Sage</a>, WordPress, and PHP. Planned with <a href="https://cursor.com" rel="noopener" target="_blank">Cursor AI</a>.</p>
  </div>
</footer>
