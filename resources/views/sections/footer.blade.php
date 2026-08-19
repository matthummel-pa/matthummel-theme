@php
  $mhSoc = \App\mh_social_links();
  $gh = \App\Github::fetchUser(\App\mh_github_login());
  $footerName = $gh['name'] ?: 'Matt Hummel';
  $footerBlurb = __('Notes, code, and Gettysburg work. Visitors, new developers, and business owners are welcome.', 'sage');
  if (! empty($gh['location'])) {
      $footerBlurb = sprintf(
          /* translators: %s: GitHub location */
          __('Notes, code, and work from %s. Visitors, new developers, and business owners are welcome.', 'sage'),
          $gh['location']
      );
  }
@endphp
<footer class="site-footer">
  <div class="container wide footer-grid">
    <div>
      <p class="brand-name footer-brand">{{ $footerName }}</p>
      <p>{{ \App\field('footer_blurb', $footerBlurb, \App\mh_front_id()) }}</p>
    </div>
    @if (has_nav_menu('primary_navigation'))
      <nav aria-label="{{ __('Footer', 'sage') }}">
        {!! wp_nav_menu([
          'theme_location' => 'primary_navigation',
          'menu_class'     => 'footer-nav',
          'echo'           => false,
          'container'      => false,
          'depth'          => 1,
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
  </div>
  <p class="footer-copy">&copy; {{ date('Y') }} {{ $footerName }}</p>
</footer>
