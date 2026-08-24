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
      <p class="footer-blurb">WordPress developer. Based in Gettysburg, PA. Open for new work.</p>
      @if (! empty($gh['hireable']))
        <p class="footer-avail">
          <span class="h-badge__dot" aria-hidden="true"></span>
          Open for work — full-time, freelance, or agency overflow
        </p>
      @else
        <p class="footer-avail">
          <span class="h-badge__dot" aria-hidden="true"></span>
          Open for new work
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
        <li><a href="{{ home_url('/projects/') }}">Example sites</a></li>
        <li><a href="{{ home_url('/code/') }}">Code &amp; GitHub</a></li>
        <li><a href="{{ home_url('/contact/') }}">Hire me</a></li>
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
        <li><a href="{{ home_url('/accessibility/') }}">Accessibility</a></li>
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
    <p class="footer-stack">Built with <a href="https://roots.io/sage/" rel="noopener" target="_blank">Sage</a>, WordPress, and PHP.</p>
  </div>
</footer>
