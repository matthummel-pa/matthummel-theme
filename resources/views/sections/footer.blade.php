@php
  $gh          = \App\Github::fetchUser(\App\mh_github_login());
  $footerName  = $gh['name'] ?: 'Matt Hummel';
  $ghUrl       = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $writing     = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
@endphp
<footer class="site-footer">
  <div class="container wide footer-inner">

    {{-- Brand + blurb --}}
    <div class="footer-brand-col">
      <a class="footer-brand-link" href="{{ home_url('/') }}" rel="home">
        <span class="brand-name footer-brand">{{ $footerName }}</span>
      </a>
      <p class="footer-blurb">{{ __('Full-stack developer and WordPress specialist in Gettysburg. I build web software businesses can own and development teams can maintain.', 'sage') }}@if (\App\mh_is_hireable($gh)) {{ __('Open for new work and collaboration.', 'sage') }}@endif</p>
      @if (\App\mh_is_hireable($gh))
        <p class="footer-avail">
          @include('partials.avail-mark', ['gh' => $gh])
          {{ \App\mh_availability_label($gh, __('Open for work', 'sage')) }} — full-time, freelance, or agency overflow
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
      <p class="footer-nav-label">Work</p>
      <ul class="footer-nav">
        <li><a href="{{ home_url('/services/') }}">Services</a></li>
        <li><a href="{{ home_url('/hire/') }}">Hire me</a></li>
        <li><a href="{{ home_url('/projects/') }}">Example sites</a></li>
        <li><a href="{{ home_url('/uses/') }}">Uses</a></li>
        <li><a href="{{ home_url('/code/') }}">Code &amp; GitHub</a></li>
        <li><a href="{{ home_url('/changelog/') }}">Changelog</a></li>
      </ul>
    </nav>

    {{-- Site --}}
    <nav class="footer-nav-col" aria-label="Site">
      <p class="footer-nav-label">Site</p>
      <ul class="footer-nav">
        <li><a href="{{ home_url('/about/') }}">About</a></li>
        <li><a href="{{ $writing }}">Journal</a></li>
        <li><a href="{{ home_url('/now/') }}">Now</a></li>
        <li><a href="{{ home_url('/contact/') }}">Contact</a></li>
        @if (\App\mh_woocommerce_is_active())
          <li><a href="{{ esc_url(wc_get_page_permalink('shop')) }}">{{ __('Shop', 'sage') }}</a></li>
          <li><a href="{{ esc_url(wc_get_page_permalink('cart')) }}">{{ __('Cart', 'sage') }}</a></li>
        @endif
      </ul>
    </nav>

    {{-- Elsewhere --}}
    <div class="footer-nav-col">
      <p class="footer-nav-label">Elsewhere</p>
      @include('partials.social')
    </div>

  </div>

  <div class="footer-bottom container wide">
    <p class="footer-copy">&copy; {{ date('Y') }} {{ $footerName }}. Gettysburg, PA.</p>
    <nav class="footer-legal-links" aria-label="Legal">
      <a href="{{ home_url('/privacy-policy/') }}">Privacy</a>
      <span aria-hidden="true">·</span>
      <a href="{{ home_url('/terms-of-use/') }}">Terms</a>
      <span aria-hidden="true">·</span>
      <a href="{{ home_url('/accessibility/') }}">Accessibility</a>
    </nav>
    <p class="footer-stack">Built with <a href="https://roots.io/sage/" rel="noopener" target="_blank">Sage</a>, WordPress, and PHP. Planned with <a href="https://cursor.com" rel="noopener" target="_blank">Cursor AI</a>.</p>
  </div>
</footer>
